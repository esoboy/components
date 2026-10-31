<?php

/*
 * Carousel on the Layout page: Previous and Next scroll the track a slide at a time and
 * are enabled only while there is somewhere to go.
 */

const CAROUSEL = '[data-slot="carousel"] >> nth=0';

it('scrolls with its buttons, which follow where it is', function () {
    $previous = '[data-slot="carousel"] >> nth=0 >> [data-carousel="previous"]';
    $next = '[data-slot="carousel"] >> nth=0 >> [data-carousel="next"]';

    visit('/layout')
        ->assertDisabled($previous)
        ->assertEnabled($next)
        ->click($next)
        ->assertEnabled($previous)
        // Each click scrolls smoothly from where the track is; let it get there.
        ->wait(0.6)->click($next)
        ->wait(0.6)->click($next)
        ->wait(0.6)->click($next)
        ->assertDisabled($next)
        ->assertNoJavaScriptErrors();
});

it('moves with the arrow keys', function () {
    visit('/layout')
        ->keys('[data-slot="carousel-content"] >> nth=0', 'ArrowRight')
        ->assertScript("document.querySelector('[data-slot=\"carousel-content\"]').scrollLeft > 0");
});
