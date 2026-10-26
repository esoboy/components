<?php

/*
 * Checkbox, Switch and Radio Group are native inputs, so wire:model binds directly.
 */

it('renders a checkbox', function () {
    $this->blade('<x-ui::checkbox id="terms" wire:model="terms" />')
        ->assertSee('<input type="checkbox" data-slot="checkbox" id="terms" wire:model="terms">', false);
});

it('shows the error of a standalone checkbox', function () {
    $this->withViewErrors(['terms' => 'The terms field must be accepted.'])
        ->blade('<x-ui::checkbox id="terms" wire:model="terms" />')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('The terms field must be accepted.');
});

it('leaves the error of a checkbox set to field.error', function () {
    $view = $this->withViewErrors(['channels' => 'Pick one.'])
        ->blade(<<<'BLADE'
            <x-ui::checkbox value="email" wire:model="channels" />
            <x-ui::checkbox value="sms" wire:model="channels" />
            <x-ui::field.error name="channels" />
            BLADE);

    expect(substr_count($view, 'aria-invalid="true"'))->toBe(2)
        ->and(substr_count($view, 'Pick one.'))->toBe(1);
});

it('renders a switch', function () {
    $this->blade('<x-ui::switch wire:model="notify" size="sm" />')
        ->assertSee('<input type="checkbox" role="switch" data-slot="switch" data-size="sm" wire:model="notify">', false);
});

it('shows switch errors', function () {
    $this->withViewErrors(['notify' => 'Required.'])
        ->blade('<x-ui::switch wire:model="notify" />')
        ->assertSee('Required.');
});

it('renders a radio group bound on the group', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::radio-group wire:model="plan">
            <x-ui::radio-group.item value="starter" />
            <x-ui::radio-group.item value="pro" />
        </x-ui::radio-group>
        BLADE);

    $view->assertSee('<ui-radio-group role="radiogroup" data-slot="radio-group" wire:model="plan">', false)
        ->assertSee('<input type="radio" data-slot="radio-group-item" value="starter">', false)
        ->assertSee('<input type="radio" data-slot="radio-group-item" value="pro">', false);
});

it('checks the item matching the default value', function () {
    $view = $this->blade(<<<'BLADE'
        <x-ui::radio-group name="plan" default-value="pro">
            <x-ui::radio-group.item value="starter" />
            <x-ui::radio-group.item value="pro" />
        </x-ui::radio-group>
        BLADE);

    $view->assertSee('value="starter">', false)
        ->assertSee('value="pro" checked="checked">', false);
});

it('shows the radio group error once, after the group', function () {
    $view = $this->withViewErrors(['plan' => 'Choose a plan.'])
        ->blade(<<<'BLADE'
            <x-ui::radio-group wire:model="plan">
                <x-ui::radio-group.item value="starter" />
            </x-ui::radio-group>
            BLADE);

    $view->assertSee('aria-invalid="true"', false)
        ->assertSeeInOrder(['</ui-radio-group>', 'Choose a plan.'], false);

    expect(substr_count($view, 'Choose a plan.'))->toBe(1);
});
