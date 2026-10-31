<?php

/*
 * Anchor and Back to Top on the Navigation page: the item of the section being read is
 * aria-current, and Back to Top shows past its threshold and scrolls back up.
 */

it('marks the section being read as the page scrolls', function () {
    visit('/navigation')
        ->resize(1440, 900)
        ->assertAttribute('[data-slot="anchor-item"][href="#sidebar"]', 'aria-current', 'location')
        ->click('[data-slot="anchor-item"][href="#tabs"]')
        ->assertAttribute('[data-slot="anchor-item"][href="#tabs"]', 'aria-current', 'location')
        ->assertAttributeMissing('[data-slot="anchor-item"][href="#sidebar"]', 'aria-current')
        ->assertNoJavaScriptErrors();
});

it('shows Back to Top past its threshold and scrolls back up', function () {
    $page = visit('/navigation')
        ->assertScript("document.querySelector('[data-slot=\"back-to-top\"]').hidden");

    $page->script('window.scrollTo(0, 1200)');

    $page->assertScript("document.querySelector('[data-slot=\"back-to-top\"]').hidden", false)
        ->click('[data-slot="back-to-top"] button')
        ->assertScript('window.scrollY', 0)
        ->assertScript("document.querySelector('[data-slot=\"back-to-top\"]').hidden");
});
