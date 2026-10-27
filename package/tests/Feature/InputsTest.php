<?php

/*
 * Tags Input and Number Input.
 */

it('renders tags as chips before the input, with a template for new ones', function () {
    $html = (string) $this->blade('<x-ui::tags-input :default-value="[\'php\', \'vue\']" name="tags" placeholder="Add" :max="3" class="w-80" />');

    expect($html)
        ->toContain('<ui-tags-input data-slot="tags-input" data-value="[&quot;php&quot;,&quot;vue&quot;]" data-max="3" data-name="tags" class="w-80">')
        ->toContain('<template data-tags-input-template>')
        ->toContain('data-tags-input-remove')
        ->toContain('<span data-slot="tags-input-tag" wire:key="tag-0"><span data-tags-input-label>php</span></span>')
        ->toContain('<input type="text" data-slot="tags-input-input" placeholder="Add" autocomplete="off"')
        ->toContain('<input type="hidden" name="tags[]" value="vue">');
});

it('renders a number input as an input group with steppers', function () {
    $html = (string) $this->blade('<x-ui::number-input :default-value="1" :min="1" :max="10" step="0.5" name="qty" />');

    expect($html)
        ->toContain('<ui-number-input data-slot="number-input" data-min="1" data-max="10" data-step="0.5">')
        ->toContain('<div role="group" data-slot="input-group">')
        ->toMatch('/data-number-input="decrement" aria-label="Decrease" tabindex="-1" disabled="disabled"/')
        ->toContain('<input type="text" inputmode="decimal" role="spinbutton" data-slot="input-group-control" value="1"')
        ->toContain('name="qty"')
        ->toMatch('/aria-valuemin="1"\s+aria-valuemax="10"\s+aria-valuenow="1"/')
        ->toMatch('/data-number-input="increment" aria-label="Increase" tabindex="-1"\s*>/');
});

it('treats a non-numeric value as empty', function () {
    $this->blade('<x-ui::number-input default-value="abc" />')
        ->assertSee('value=""', false)
        ->assertDontSee('aria-valuenow', false);
});
