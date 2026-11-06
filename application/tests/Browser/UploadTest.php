<?php

it('renders multiple pending files and clears them after cancellation', function () {
    $page = visit('/uploads');
    $list = '[data-slot="file-upload"] >> nth=0 >> [data-file-upload-list]';
    $page->script(<<<'JS'
        (() => {
            const input = document.querySelector('[data-slot="file-upload"] input[type="file"]');
            const files = new DataTransfer();
            files.items.add(new File(['notes'], 'notes.txt', { type: 'text/plain' }));
            files.items.add(new File(['report'], 'report.txt', { type: 'text/plain' }));
            input.files = files.files;
            input.dispatchEvent(new CustomEvent('livewire-upload-start', { bubbles: true }));
        })()
        JS);
    $page->assertSeeIn($list, 'notes.txt')->assertSeeIn($list, 'report.txt');
    $page->script("document.querySelector('[data-slot=file-upload] input').dispatchEvent(new CustomEvent('livewire-upload-cancel', { bubbles: true }))");
    $page->assertDontSee('notes.txt')->assertDontSee('report.txt')->assertNoJavaScriptErrors();
});

it('updates the shared progress bar and resets it for the next upload', function () {
    $page = visit('/uploads');
    $progress = '[data-slot="file-upload"] >> nth=0 >> [data-file-upload-progress]';

    $page->script(<<<'JS'
        (() => {
            const input = document.querySelector('[data-slot="file-upload"] input');
            input.dispatchEvent(new CustomEvent('livewire-upload-start', { bubbles: true }));
            input.dispatchEvent(new CustomEvent('livewire-upload-progress', { bubbles: true, detail: { progress: 42 } }));
        })()
        JS);
    $page->assertAttribute($progress, 'aria-valuenow', '42')
        ->assertAttribute($progress, 'aria-valuetext', '42%')
        ->assertScript("document.querySelector('[data-file-upload-progress] [data-slot=\"progress-indicator\"]').style.width", '42%');

    $page->script(<<<'JS'
        (() => {
            const input = document.querySelector('[data-slot="file-upload"] input');
            input.dispatchEvent(new CustomEvent('livewire-upload-progress', { bubbles: true, detail: { progress: 100 } }));
        })()
        JS);
    $page->assertScript("document.querySelector('[data-file-upload-progress]').hasAttribute('data-complete')");
    $page->script("document.querySelector('[data-slot=\"file-upload\"] input').dispatchEvent(new CustomEvent('livewire-upload-finish', { bubbles: true }))");
    $page->assertScript("document.querySelector('[data-file-upload-progress]').hidden");
    $page->script("document.querySelector('[data-slot=\"file-upload\"] input').dispatchEvent(new CustomEvent('livewire-upload-start', { bubbles: true }))");
    $page->assertAttribute($progress, 'aria-valuenow', '0')
        ->assertScript("document.querySelector('[data-file-upload-progress]').hasAttribute('data-complete')", false)
        ->assertNoJavaScriptErrors();
});
