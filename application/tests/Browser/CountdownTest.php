<?php

it('restores a named deadline after navigation and reports expiry to the caller', function () {
    $clock = '[data-countdown-clock][data-name="flash-sale"]';
    $deadline = "document.querySelector('".$clock."')?.dataset.deadline ?? null";
    $page = visit('/data')->assertPresent($clock);
    $original = $page->script($deadline);

    $page->script("Livewire.navigate('/general')");
    $page->assertPathIs('/general');
    $page->script("Livewire.navigate('/data')");
    $page->assertPathIs('/data')->assertScript($deadline, $original)
        ->click('Restart the sale at 10s')
        ->assertSeeIn($clock, '00:10');

    $page->script("Livewire.dispatch('ui-countdown', { name: 'flash-sale', seconds: 2 })");
    $page->assertSeeIn($clock, '00:00')
        ->assertSee('The flash sale has ended')
        ->assertNoJavaScriptErrors();
});
