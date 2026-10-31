<?php

/*
 * Number Input on the Inputs page: − and + buttons and the arrow keys step within
 * min and max.
 */

const QUANTITY = 'eso-number-input[wire\\:model\\.live="quantity"]';

it('steps with its buttons within its bounds', function () {
    visit('/inputs')
        ->click(QUANTITY.' [data-number-input="increment"]')
        ->assertSeeIn('#quantity', '3')
        ->click(QUANTITY.' [data-number-input="decrement"]')
        ->click(QUANTITY.' [data-number-input="decrement"]')
        ->assertSeeIn('#quantity', '1')
        ->assertDisabled(QUANTITY.' [data-number-input="decrement"]')
        ->assertNoJavaScriptErrors();
});

it('steps with the arrow keys', function () {
    visit('/inputs')
        ->keys(QUANTITY.' input', 'ArrowUp')
        ->assertSeeIn('#quantity', '3');
});

it('steps decimals without float errors', function () {
    visit('/inputs')
        ->click('eso-number-input[wire\\:model\\.live="price"] [data-number-input="increment"]')
        ->assertSeeIn('#price', '10')
        ->assertValue('eso-number-input[wire\\:model\\.live="price"] input', '10');
});
