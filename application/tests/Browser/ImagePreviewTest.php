<?php

/*
 * Image Preview on the Uploads page: the images open in one modal viewer that pages
 * through them.
 */

const VIEWER = '#gallery-preview [data-slot="image-preview-content"]';

it('opens the clicked image in a modal viewer', function () {
    visit('/uploads')
        ->click('#gallery-preview [data-slot="image-preview-trigger"] >> nth=1')
        ->assertScript(opened(VIEWER))
        ->assertAttributeContains(VIEWER.' [data-slot="image-preview-image"]', 'src', 'photo-2.svg')
        ->assertSeeIn(VIEWER.' [data-slot="image-preview-counter"]', '2 / 4')
        ->assertNoJavaScriptErrors();
});

it('pages through the images and closes', function () {
    visit('/uploads')
        ->click('#gallery-preview [data-slot="image-preview-trigger"] >> nth=0')
        ->click(VIEWER.' [data-image-preview="next"]')
        ->assertAttributeContains(VIEWER.' [data-slot="image-preview-image"]', 'src', 'photo-2.svg')
        ->keys(VIEWER, 'ArrowLeft')
        ->assertAttributeContains(VIEWER.' [data-slot="image-preview-image"]', 'src', 'photo-1.svg')
        ->click(VIEWER.' [data-image-preview="close"]')
        ->assertScript(opened(VIEWER), false);
});

it('shows no paging for a single image', function () {
    visit('/uploads')
        ->click('#single-preview [data-slot="image-preview-trigger"]')
        ->assertScript(opened('#single-preview [data-slot="image-preview-content"]'))
        ->assertScript("document.querySelector('#single-preview [data-image-preview=\"next\"]').checkVisibility()", false);
});
