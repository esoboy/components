<?php

/*
 * Tags Input on the Inputs page: Enter or a comma adds a tag, Backspace in the empty
 * input removes the last, up to its max.
 */

const TAGS_INPUT = '[data-slot="tags-input-input"]';

it('adds tags with Enter and a comma', function () {
    visit('/inputs')
        ->assertSeeIn('#tags', 'laravel, livewire')
        ->type(TAGS_INPUT, 'php')
        ->keys(TAGS_INPUT, 'Enter')
        ->assertSeeIn('#tags', 'laravel, livewire, php')
        ->type(TAGS_INPUT, 'pest,')
        ->assertSeeIn('#tags', 'laravel, livewire, php, pest')
        ->assertValue(TAGS_INPUT, '')
        ->assertNoJavaScriptErrors();
});

it('removes the last tag with Backspace in the empty input', function () {
    visit('/inputs')
        ->keys(TAGS_INPUT, 'Backspace')
        ->assertSeeIn('#tags', 'laravel')
        ->assertCount('[data-slot="tags-input-tag"]', 1);
});

it('stops at its max', function () {
    visit('/inputs')
        ->type(TAGS_INPUT, 'a,b,c,d,')
        ->assertSeeIn('#tags', 'laravel, livewire, a, b, c')
        ->assertCount('[data-slot="tags-input-tag"]', 5);
});
