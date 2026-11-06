<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Editor on the Editor page: Tiptap loads with its own script and binds HTML with
 * wire:model, the format decides its tools, Ui::editor() inserts the app's media, and
 * Rich Text shows the published, sanitized HTML.
 */

const ARTICLE = '[data-slot="editor"][data-editor-name="article"]';

it('inserts a server-rendered slash command and emoji suggestion', function () {
    $input = ARTICLE.' .ProseMirror';
    $page = visit('/editor')->assertPresent($input);
    $page->click($input)->keys($input, 'Control+a')->keys($input, 'Backspace');
    $page->typeSlowly($input, '/head', 20);
    $page->assertSeeIn(ARTICLE.' [data-editor-suggestion="slash"]', 'Heading 1')
        ->keys($input, 'Enter')
        ->assertPresent($input.' h1');

    $page->typeSlowly($input, ':smile', 20);
    $page->assertScript("document.querySelector('[data-editor-name=article] [data-editor-suggestion=emoji] [role=option]')?.checkVisibility() === true")
        ->keys($input, 'Enter')
        ->assertScript("/\p{Extended_Pictographic}/u.test(document.querySelector('[data-editor-name=article] .ProseMirror').textContent)")
        ->assertNoJavaScriptErrors();
});

it('sends pasted files as multipart and inserts the uploader response', function () {
    // Pest's HTTP driver does not parse multipart files. Check the browser's
    // actual request body here; package feature tests exercise the PHP uploader.
    Route::middleware('web')->post('/browser-editor-upload', function (Request $request) {
        expect($request->header('content-type'))->toStartWith('multipart/form-data; boundary=');
        expect($request->getContent())->toContain('name="source"', 'paste', 'filename="upload-check.txt"', 'Upload integration check');

        return response()->json(['attachment' => ['url' => '/demo/guide.pdf', 'name' => 'upload-check.txt', 'mime' => 'text/plain', 'size' => 24]]);
    });

    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');
    $page->script(<<<'JS'
        (() => {
            document.querySelector('[data-editor-name="article"]').dataset.upload = '/browser-editor-upload';
            const input = document.querySelector('[data-editor-name="article"] .ProseMirror');
            const clipboard = new DataTransfer();
            clipboard.items.add(new File(['Upload integration check'], 'upload-check.txt', { type: 'text/plain' }));
            input.focus();
            input.dispatchEvent(new ClipboardEvent('paste', { clipboardData: clipboard, bubbles: true, cancelable: true }));
        })()
        JS);
    $page->assertPresent(ARTICLE.' a[href="/demo/guide.pdf"][download="upload-check.txt"]')
        ->assertNoJavaScriptErrors();
});

it('executes a radio menu choice and follows the next document selection', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');

    $page->script(<<<'JS'
        (() => {
            const element = document.querySelector('[data-editor-name="article"]');
            Livewire.find(element.closest('[wire\\:id]').getAttribute('wire:id')).$set('article', '<p>First</p><p>Second</p>');
        })()
        JS);
    $page->assertSeeIn(ARTICLE.' .ProseMirror', 'First');
    $page->script(<<<'JS'
        (() => {
            const p = document.querySelector('[data-editor-name="article"] .ProseMirror p');
            p.closest('.ProseMirror').focus();
            const range = document.createRange(); range.selectNodeContents(p);
            getSelection().removeAllRanges(); getSelection().addRange(range);
        })()
        JS);

    $menu = ARTICLE.' [data-editor-menu-content="font-size"]';
    $page->click(ARTICLE.' [data-editor-menu="font-size"]')
        ->click($menu.' label:has(input[data-editor-value="18px"])')
        ->assertScript(opened($menu), false)
        ->assertScript("document.querySelector('[data-editor-name=\"article\"] .ProseMirror p span').style.fontSize", '18px')
        ->assertScript("document.querySelector('[data-editor-name=\"article\"] input[data-editor-command=\"font-size\"][data-editor-value=\"18px\"]').checked");

    $page->script(<<<'JS'
        (() => {
            const p = document.querySelector('[data-editor-name="article"] .ProseMirror p:last-child');
            p.closest('.ProseMirror').focus();
            const range = document.createRange(); range.setStart(p.firstChild, 1); range.collapse(true);
            getSelection().removeAllRanges(); getSelection().addRange(range);
        })()
        JS);
    $page->assertScript("document.querySelector('[data-editor-name=\"article\"] input[data-editor-command=\"font-size\"][data-editor-value=\"18px\"]').checked", false)
        ->assertScript("document.querySelector('[data-editor-name=\"article\"] input[data-editor-command=\"font-size\"][data-editor-value=\"\"]').checked")
        ->assertNoJavaScriptErrors();
});

it('loads Tiptap and edits the bound HTML', function () {
    visit('/editor')
        ->assertPresent(ARTICLE.' .ProseMirror[contenteditable="true"]')
        ->assertScript("document.querySelector('".ARTICLE." .ProseMirror h2').textContent", 'Media')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

it('applies a mark from its toolbar', function () {
    $page = visit('/editor');

    $page->script("(() => { const p = document.querySelector('[data-slot=\"editor\"] .ProseMirror p'); const range = document.createRange(); range.selectNodeContents(p.firstChild); getSelection().removeAllRanges(); getSelection().addRange(range); p.closest('.ProseMirror').focus() })()");

    $page->click('[data-slot="editor"] >> nth=0 >> [data-editor-command="bold"]')
        ->assertPresent('[data-slot="editor"] >> nth=0 >> .ProseMirror p strong');
});

it('offers only the tools its format has', function () {
    visit('/editor')
        ->assertNotPresent('[data-slot="editor"] >> nth=0 >> [data-editor-command="image"], [data-slot="editor"] >> nth=0 >> [data-editor-action="image"]')
        ->assertPresent(ARTICLE.' [data-editor-command="bold"]');
});

it('inserts media from the server with Ui::editor()', function () {
    visit('/editor')
        ->click('Insert from the library')
        ->assertScript(opened('#library'))
        ->click('#library label:has-text("photo-1.svg")')
        ->click('#library button:has-text("Insert")')
        ->assertScript(opened('#library'), false)
        ->assertPresent(ARTICLE.' .ProseMirror img[src="/demo/photo-1.svg"]');
});

it('publishes sanitized HTML that Rich Text shows with a table of contents', function () {
    visit('/editor')
        ->click('Publish')
        ->assertScript("document.querySelector('#published h2').textContent", 'Media')
        ->assertPresent('#published h2[id]')
        ->assertPresent('[data-slot="anchor-item"]');
});

it('scrolls long content inside the editor while keeping its toolbar in place', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');

    $page->script(<<<'JS'
        (() => {
            const editor = document.querySelector('[data-editor-name="article"]');
            Livewire.find(editor.closest('[wire\\:id]').getAttribute('wire:id')).$set('article', '<p>Line</p>'.repeat(80));
            editor.scrollIntoView({ block: 'center' });
        })()
        JS);
    $page->assertSeeIn(ARTICLE.' .ProseMirror p >> nth=0', 'Line');
    $page->page()->waitForLoadState('networkidle');
    $page->assertScript(<<<'JS'
        (() => {
            const content = document.querySelector('[data-editor-name="article"] [data-slot="editor-content"]');
            return content.clientHeight <= 384 && content.scrollHeight > content.clientHeight;
        })()
        JS);
    $page->script(<<<'JS'
        (() => {
            const editor = document.querySelector('[data-editor-name="article"]');
            window.editorToolbarTop = editor.querySelector('[data-slot="editor-toolbar"]').getBoundingClientRect().top;
            editor.querySelector('[data-slot="editor-content"]').scrollTop = 300;
        })()
        JS);
    $page->assertScript(<<<'JS'
        (() => {
            const editor = document.querySelector('[data-editor-name="article"]');
            return editor.querySelector('[data-slot="editor-content"]').scrollTop === 300
                && Math.abs(editor.querySelector('[data-slot="editor-toolbar"]').getBoundingClientRect().top - window.editorToolbarTop) < 1;
        })()
        JS)->assertNoJavaScriptErrors();
});

it('shows fullscreen tooltips below the toolbar', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');

    $page->script("document.querySelector('[data-editor-name=\"article\"] [data-editor-action=\"fullscreen\"]').click()");
    $page->hover(ARTICLE.' [data-editor-command="bold"]')
        ->assertScript(<<<'JS'
            (() => {
                const editor = document.querySelector('[data-editor-name="article"]');
                const tip = editor.querySelector('[data-editor-tip]');
                const button = editor.querySelector('[data-editor-command="bold"]').getBoundingClientRect();
                const box = tip.getBoundingClientRect();
                return tip.matches(':popover-open') && box.top >= button.bottom && box.bottom <= innerHeight;
            })()
            JS)->assertNoJavaScriptErrors();
});

it('wraps all toolbar controls without More when collapsing is disabled', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');

    $page->assertNotPresent(ARTICLE.' [data-editor-more]');
    $page->script("document.querySelector('[data-editor-name=\"article\"]').style.width = '320px'");
    $page->assertScript(<<<'JS'
        (() => {
            const toolbar = document.querySelector('[data-editor-name="article"] [data-slot="editor-toolbar"]');
            const buttons = [...toolbar.querySelectorAll('button')].filter(button => !button.closest('[popover]'));
            const box = toolbar.getBoundingClientRect();
            return toolbar.scrollWidth <= toolbar.clientWidth
                && new Set(buttons.map(button => button.getBoundingClientRect().top)).size > 1
                && buttons.every(button => {
                    const rect = button.getBoundingClientRect();
                    return rect.width > 0 && rect.left >= box.left && rect.right <= box.right && rect.bottom <= box.bottom;
                });
        })()
        JS)->assertNoJavaScriptErrors();
});

it('fits the collapsed toolbar and its Blade groups on a mobile viewport', function () {
    $basic = '[data-slot="editor"][data-editor-name="intro"]';
    $page = visit('/editor');
    $page->page()->setViewportSize(390, 844);
    $page->click($basic.' [data-editor-more]')
        ->assertScript(opened($basic.' [data-editor-more-content]'))
        ->assertPresent($basic.' [data-editor-overflow-group]:not([hidden]) button')
        ->assertNoJavaScriptErrors();
});
