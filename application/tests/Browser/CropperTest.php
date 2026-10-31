<?php

/*
 * Cropper on the Uploads page: the arrow keys pan the image under the crop area, which
 * binds the crop in the image's pixels; the server cuts it out.
 */

it('binds the crop as it pans', function () {
    $page = visit('/uploads')
        ->assertScript("document.getElementById('banner-cropper').value?.width > 0");

    $before = $page->script("JSON.stringify(document.getElementById('banner-cropper').value)");

    // The crop spans the image's width at the least zoom, so only up and down move it.
    $page->keys('#banner-cropper', 'ArrowDown')
        ->assertScript("document.getElementById('banner-value').textContent.trim().startsWith('{')")
        ->assertScript("JSON.stringify(document.getElementById('banner-cropper').value) !== ".json_encode($before))
        ->assertNoJavaScriptErrors();
});

it('keeps the crop at its aspect ratio', function () {
    visit('/uploads')
        ->assertScript("(({ width, height }) => Math.abs(width / height - 16 / 9) < 0.05)(document.getElementById('banner-cropper').value)");
});

it('sends the crop for the server to cut', function () {
    visit('/uploads')
        ->keys('#avatar-cropper', 'ArrowLeft')
        ->click('Crop on the server')
        ->assertAttributeContains('#cropped', 'src', 'data:image/jpeg;base64,');
});
