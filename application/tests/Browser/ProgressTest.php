<?php

/*
 * Progress on the Layout page: bars and circles follow the bound value.
 */

it('follows its value as a bar and a circle', function () {
    visit('/layout')
        ->assertAttribute('[data-slot="progress"] >> nth=0', 'aria-valuenow', '60')
        ->click('Advance')
        ->assertAttribute('[data-slot="progress"] >> nth=0', 'aria-valuenow', '70')
        ->assertAttribute('[data-slot="progress"] >> nth=0', 'aria-valuetext', '70%')
        ->assertScript("document.querySelector('[data-slot=\"progress-indicator\"]').style.width", '70%')
        ->assertScript("document.querySelector('[data-slot=\"progress-circle-indicator\"]').style.strokeDashoffset", '30')
        ->assertNoJavaScriptErrors();
});

it('is indeterminate without a value', function () {
    visit('/layout')
        ->assertAttributeMissing('[data-slot="progress"][aria-label="Loading"]', 'aria-valuenow');
});
