<?php

/*
 * Toggle and Toggle Group on the Controls page: native checkboxes and radios inside
 * their labels, bound with wire:model.
 */

function toggle(string $label): string
{
    return "label:has(> input[aria-label=\"{$label}\"])";
}

it('binds a toggle', function () {
    visit('/controls')
        ->assertSeeIn('#bold', 'off')
        ->click(toggle('Toggle bold'))
        ->assertSeeIn('#bold', 'on')
        ->assertChecked('[aria-label="Toggle bold"]')
        ->assertNoJavaScriptErrors();
});

it('starts pressed and stays put while disabled', function () {
    visit('/controls')
        ->assertChecked('[aria-label="Toggle italic"]')
        ->assertDisabled('[data-slot="toggle"][data-size="sm"] input');
});

it('binds a single toggle group', function () {
    visit('/controls')
        ->assertSeeIn('#align', 'left')
        ->click('[data-slot="toggle-group-item"]:has-text("Center")')
        ->assertSeeIn('#align', 'center');
});

it('binds a multiple toggle group', function () {
    visit('/controls')
        ->assertSeeIn('#styles', 'bold')
        ->click(toggle('Italic'))
        ->assertSeeIn('#styles', 'bold,italic')
        ->click(toggle('Bold'))
        ->assertSeeIn('#styles', 'italic');
});
