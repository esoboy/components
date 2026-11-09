<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Editor on the Editor page: Tiptap loads with its own script and binds HTML with
 * wire:model, the format decides its tools, Ui::editor() inserts the app's media, and
 * Rich Text shows the published, sanitized HTML.
 */

const ARTICLE = '[data-slot="editor"][data-editor-name="article"]';

it('shows the selected link URL in the Blade rendered link toolbar', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');
    $page->script(<<<'JS'
        Livewire.first().$set('article', '<p>See <a href="https://example.org/guide">the guide</a>.</p>')
        JS);
    $page->assertSeeIn(ARTICLE.' .ProseMirror a', 'the guide');
    $page->click(ARTICLE.' .ProseMirror a');
    $link = ARTICLE.' [data-editor-link-href]';
    $page->assertAttribute($link, 'href', 'https://example.org/guide')
        ->assertSeeIn($link, 'https://example.org/guide')
        ->assertScript("document.querySelector('[data-editor-name=article] [data-editor-link-href]').checkVisibility({visibilityProperty: true})")
        ->assertNoJavaScriptErrors();
});

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

it('lets the application handle a pasted file and inserts its attachment', function () {
    // Pest's HTTP driver does not parse multipart files. Check the browser's actual request body.
    Route::middleware('web')->post('/browser-editor-upload', function (Request $request) {
        expect($request->header('content-type'))->toStartWith('multipart/form-data; boundary=');
        expect($request->getContent())->toContain('name="source"', 'paste', 'filename="upload-check.txt"', 'Upload integration check');

        return response()->json(['attachment' => ['url' => '/demo/guide.pdf', 'name' => 'upload-check.txt', 'mime' => 'text/plain', 'size' => 24]]);
    });

    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');
    $page->script(<<<'JS'
        (() => {
            document.querySelector('[data-demo-media-endpoint]').dataset.demoMediaEndpoint = '/browser-editor-upload';
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

it('inserts library media at the original selection and isolates the two editors', function () {
    $intro = '[data-editor-name="intro"]';
    $image = ' [data-editor-action="media"][data-editor-value="image"]';
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror')->assertPresent($intro.' .ProseMirror');
    $page->script(<<<'JS'
        Livewire.first().$set('article', '<p>Before</p><p>After</p>', false)
        Livewire.first().$set('intro', '<p>Introduction</p>', false)
        JS);
    $page->assertSeeIn(ARTICLE.' .ProseMirror', 'Before')
        ->click(ARTICLE.' .ProseMirror p:first-child')
        ->keys(ARTICLE.' .ProseMirror', 'End')
        ->click(ARTICLE.$image)
        ->assertScript(opened('#library'))
        ->click('#library label:has-text("photo-1.svg")')
        ->click('#library button:has-text("Insert")')
        ->assertPresent(ARTICLE.' .ProseMirror p:first-child + div img[src="/demo/photo-1.svg"]')
        ->assertNotPresent($intro.' .ProseMirror img');

    $page->click($intro.' .ProseMirror p')
        ->click($intro.$image)
        ->assertScript(opened('#library'))
        ->click('#library label:has-text("photo-2.svg")')
        ->click('#library button:has-text("Insert")')
        ->assertPresent($intro.' .ProseMirror img[src="/demo/photo-2.svg"]')
        ->assertNotPresent(ARTICLE.' .ProseMirror img[src="/demo/photo-2.svg"]');

    $page->click($intro.' .ProseMirror img[src="/demo/photo-2.svg"]')
        ->click($intro.' [data-editor-action="media"][aria-label="Replace"]')
        ->assertScript(opened('#library'))
        ->click('#library label:has-text("landscape.jpg")')
        ->assertPresent('#library input[type="radio"]:checked')
        ->click('#library button:has-text("Insert")')
        ->assertNotPresent($intro.' .ProseMirror img[src="/demo/photo-2.svg"]')
        ->assertPresent($intro.' .ProseMirror img[src="/demo/landscape.jpg"]')
        ->assertPresent(ARTICLE.' .ProseMirror img[src="/demo/photo-1.svg"]')
        ->assertNoJavaScriptErrors();
});

it('opens the app-owned audio input during the click and resolves a native cancellation', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');
    $page->script(<<<'JS'
        const picker = document.querySelector('[data-demo-audio-picker]')
        picker.addEventListener('click', () => {
            picker.dataset.openedWithGesture = navigator.userActivation.isActive ? 'true' : 'false'
        })
        window.addEventListener('ui-editor', event => {
            if (event.detail.name === 'article' && event.detail.action === 'resolve-media') {
                picker.dataset.cancelled = String(event.detail.attachments.length === 0)
            }
        })
        JS);
    $page->click(ARTICLE.' [data-editor-action="media"][data-editor-value="audio"]')
        ->assertAttribute('[data-demo-audio-picker]', 'data-opened-with-gesture', 'true');
    $page->script(<<<'JS'
        document.querySelector('[data-demo-audio-picker]').dispatchEvent(new Event('cancel', { bubbles: true }))
        JS);
    $page->assertAttribute('[data-demo-audio-picker]', 'data-cancelled', 'true')
        ->assertNotPresent(ARTICLE.' .ProseMirror audio')
        ->assertNoJavaScriptErrors();
});

it('cancels the application picker and discards a response to a replaced document', function () {
    $image = ARTICLE.' [data-editor-action="media"][data-editor-value="image"]';
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');
    $page->script(<<<'JS'
        document.querySelector('[data-editor-name="article"]').addEventListener('ui-editor-media-request', event => {
            window.editorMediaRequest = event.detail
        })
        JS);
    $page->click($image)->assertScript(opened('#library'))->keys('#library', 'Escape')
        ->assertScript(opened('#library'), false)
        ->assertScript('Livewire.first().$get("mediaRequest") === null');
    $page->script(<<<'JS'
        window.dispatchEvent(new CustomEvent('ui-editor', { detail: {
            ...window.editorMediaRequest, action: 'resolve-media',
            attachments: [{ url: '/cancelled.png', mime: 'image/png' }],
        } }))
        JS);
    $page->assertNotPresent(ARTICLE.' .ProseMirror img[src="/cancelled.png"]');

    $page->click($image)->assertScript(opened('#library'));
    $page->script(<<<'JS'
        Livewire.first().$set('article', '<p>New document</p>', false)
        JS);
    $page->assertSeeIn(ARTICLE.' .ProseMirror', 'New document')
        ->click('#library label:has-text("photo-1.svg")')
        ->click('#library button:has-text("Insert")')
        ->assertScript(opened('#library'), false)
        ->assertNotPresent(ARTICLE.' .ProseMirror img')
        ->assertNoJavaScriptErrors();
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

it('shows fullscreen tooltips below the toolbar without a Livewire request for their text', function () {
    $page = visit('/editor')->assertPresent(ARTICLE.' .ProseMirror');

    $page->script(<<<'JS'
        const originalFetch = window.fetch
        window.editorTipRequests = 0
        window.fetch = (url, options) => {
            if (typeof options?.body === 'string' && options.body.includes('"method":"showTip"')) window.editorTipRequests++
            return originalFetch(url, options)
        }
        document.querySelector('[data-editor-name="article"] [data-editor-action="fullscreen"]').click()
        JS);
    $page->hover(ARTICLE.' [data-editor-command="bold"]')
        ->assertScript(<<<'JS'
            (() => {
                const editor = document.querySelector('[data-editor-name="article"]');
                const tip = editor.querySelector('[data-editor-tip]:popover-open');
                if (!tip) return false;
                const button = editor.querySelector('[data-editor-command="bold"]').getBoundingClientRect();
                const box = tip.getBoundingClientRect();
                return tip.matches(':popover-open') && box.top >= button.bottom && box.bottom <= innerHeight;
            })()
            JS)->assertScript('window.editorTipRequests', 0)
        ->assertNoJavaScriptErrors();
});

it('can replace a media node next to the editor scroll boundary', function () {
    $intro = '[data-editor-name="intro"]';
    $page = visit('/editor')->assertPresent($intro.' .ProseMirror');
    $page->page()->setViewportSize(1280, 900);
    $page->script(<<<'JS'
        Livewire.first().$set('intro', '<img src="/demo/photo-2.svg">' + '<p>Below the image</p>'.repeat(35), false)
        document.querySelector('[data-editor-name="intro"]').scrollIntoView({ block: 'center' })
        JS);
    $replace = $intro.' [data-editor-action="media"][aria-label="Replace"]';
    $page->click($intro.' .ProseMirror img[src="/demo/photo-2.svg"]')->assertVisible($replace);
    $page->script(<<<'JS'
        (() => {
            const intro = document.querySelector('[data-editor-name="intro"]')
            const image = intro.querySelector('.ProseMirror img')
            const replace = intro.querySelector('[data-editor-action="media"][aria-label="Replace"]')
            const content = intro.querySelector('[data-slot="editor-content"]')
            window.editorBubbleDistance = replace.getBoundingClientRect().top - image.getBoundingClientRect().top
            content.scrollTop += 30
            window.scrollBy(0, 30)
        })()
        JS);
    $page->assertScript(<<<'JS'
        (() => {
            const intro = document.querySelector('[data-editor-name="intro"]')
            const image = intro.querySelector('.ProseMirror img')
            const replace = intro.querySelector('[data-editor-action="media"][aria-label="Replace"]')
            const distance = replace.getBoundingClientRect().top - image.getBoundingClientRect().top
            return intro.querySelector('[data-slot="editor-content"]').scrollTop >= 30
                && Math.abs(distance - window.editorBubbleDistance) < 3
        })()
        JS)
        ->click($replace)
        ->assertScript(opened('#library'))
        ->click('#library label:has-text("photo-1.svg")')
        ->click('#library button:has-text("Insert")')
        ->assertPresent($intro.' .ProseMirror img[src="/demo/photo-1.svg"]')
        ->assertNoJavaScriptErrors();
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
