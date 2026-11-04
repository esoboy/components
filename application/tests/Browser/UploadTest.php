<?php

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
