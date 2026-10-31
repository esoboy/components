<?php

/*
 * Editor on the Editor page: Tiptap loads with its own script and binds HTML with
 * wire:model, the format decides its tools, Ui::editor() inserts the app's media, and
 * Rich Text shows the published, sanitized HTML.
 */

const ARTICLE = '[data-slot="editor"][data-editor-name="article"]';

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
