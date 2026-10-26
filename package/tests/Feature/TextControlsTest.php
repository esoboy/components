<?php

/*
 * Input, Textarea and Native Select share the same validation behaviour.
 */

it('renders an input', function () {
    $this->blade('<x-ui::input wire:model="email" placeholder="Email" />')
        ->assertSee('<input type="text" data-slot="input" wire:model="email" placeholder="Email">', false);
});

it('accepts an input type', function () {
    $this->blade('<x-ui::input type="email" />')->assertSee('type="email"', false);
});

it('renders a textarea with its content', function () {
    $this->blade('<x-ui::textarea name="bio">Hello</x-ui::textarea>')
        ->assertSee('<textarea data-slot="textarea" name="bio">Hello</textarea>', false);
});

it('renders a native select whose wrapper takes the class', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::native-select wire:model="role" class="w-full" size="sm">
            <x-ui::native-select.optgroup label="Staff">
                <x-ui::native-select.option value="admin">Admin</x-ui::native-select.option>
            </x-ui::native-select.optgroup>
        </x-ui::native-select>
        BLADE);

    $view->assertSee('<div data-slot="native-select-wrapper" data-size="sm" class="w-full">', false)
        ->assertSee('<select data-slot="native-select" wire:model="role">', false)
        ->assertSee('<optgroup data-slot="native-select-optgroup" label="Staff">', false)
        ->assertSee('<option data-slot="native-select-option" value="admin">Admin</option>', false)
        ->assertSee('data-slot="native-select-icon"', false);
});

it('shows the validation error under the control', function (string $blade, string $control) {
    $this->withViewErrors(['email' => 'The email field is required.'])
        ->blade($blade)
        ->assertSee($control.' aria-invalid="true" aria-describedby="email-error" id="email" wire:model="email"', false)
        ->assertSeeInOrder([$control, '<div role="alert" data-slot="field-error" id="email-error">', 'The email field is required.'], false);
})->with([
    'input' => ['<x-ui::input id="email" wire:model="email" />', '<input type="text" data-slot="input"'],
    'textarea' => ['<x-ui::textarea id="email" wire:model="email" />', '<textarea data-slot="textarea"'],
    'native select' => ['<x-ui::native-select id="email" wire:model="email" />', '<select data-slot="native-select"'],
]);

it('keeps the control invalid but hides the message with show-error off', function (string $blade) {
    $this->withViewErrors(['email' => 'The email field is required.'])
        ->blade($blade)
        ->assertSee('aria-invalid="true"', false)
        ->assertDontSee('aria-describedby', false)
        ->assertDontSee('The email field is required.');
})->with([
    'input' => ['<x-ui::input wire:model="email" :show-error="false" />'],
    'textarea' => ['<x-ui::textarea wire:model="email" :show-error="false" />'],
    'native select' => ['<x-ui::native-select wire:model="email" :show-error="false" />'],
]);

it('stays clean without errors', function () {
    $this->withViewErrors(['other' => 'Nope.'])
        ->blade('<x-ui::input wire:model="email" />')
        ->assertDontSee('aria-invalid', false)
        ->assertDontSee('field-error', false);
});
