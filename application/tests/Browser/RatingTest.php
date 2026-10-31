<?php

/*
 * Rating on the Inputs page: stars as a radio group; clicking the chosen star clears it.
 */

it('rates on click and clears on a second click of the same star', function () {
    visit('/inputs')
        ->assertSeeIn('#score-value', '3')
        ->click('#score [data-slot="rating-item"][data-value="5"]')
        ->assertSeeIn('#score-value', '5')
        ->assertAttribute('#score [data-slot="rating-item"][data-value="5"]', 'aria-checked', 'true')
        ->click('#score [data-slot="rating-item"][data-value="5"]')
        ->assertSeeIn('#score-value', 'none')
        ->assertNoJavaScriptErrors();
});

it('moves with the arrow keys as a radio group', function () {
    visit('/inputs')
        ->keys('#score [data-slot="rating-item"][data-value="3"]', 'ArrowRight')
        ->assertSeeIn('#score-value', '4')
        ->assertScript('document.activeElement.dataset.value', '4');
});

it('shows a fractional read-only rating as an image', function () {
    visit('/inputs')
        ->assertAttribute('[data-slot="rating"][role="img"]', 'aria-label', '4.5 of 5 stars')
        ->assertScript("getComputedStyle(document.querySelectorAll('[data-slot=\"rating\"][role=\"img\"] [data-slot=\"rating-item\"]')[4]).getPropertyValue('--rating-fill').trim()", '50%');
});
