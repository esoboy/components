<?php

/*
 * Select on the Controls page: a listbox popover under its trigger, bound with
 * wire:model on the select.
 */

const SELECT_CONTENT = '#demo-select [data-slot="select-content"]';

it('shows the bound item on its trigger', function () {
    visit('/controls')
        ->assertSeeIn('#demo-select [data-slot="select-trigger"]', 'Central Standard Time')
        ->assertNoJavaScriptErrors();
});

it('opens with the chosen item selected and picks another', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->assertScript(opened(SELECT_CONTENT))
        ->assertAttribute('#demo-select [data-slot="select-item"][data-value="cst"]', 'aria-selected', 'true')
        ->click('#demo-select [data-slot="select-item"][data-value="gmt"]')
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'gmt')
        ->assertSeeIn('#demo-select [data-slot="select-trigger"]', 'Greenwich Mean Time');
});

it('moves with the arrow keys past disabled items and picks with Enter', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->keys('*:focus', ['ArrowDown', 'ArrowDown', 'Enter'])
        ->assertSeeIn('#timezone', 'gmt');
});

it('closes on Escape without changing the value', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->keys('*:focus', ['ArrowDown', 'Escape'])
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'cst');
});

it('shows a validation error inside a field', function () {
    visit('/controls')
        ->click('#fruit [data-slot="select-trigger"]')
        ->click('#fruit [data-slot="select-item"][data-value="grapes"]')
        ->click('Pick')
        ->assertSee('The selected fruit is invalid.')
        ->assertAttribute('#fruit [data-slot="select-trigger"]', 'aria-invalid', 'true');
});
