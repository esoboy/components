@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- An Input Group with − and + buttons; arrows step too, and holding a button repeats. --}}
@props([
    'min' => null,
    'max' => null,
    'step' => 1,
    'placeholder' => null,
    'disabled' => false,
    'value' => null,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value);
$value = is_numeric($value) ? $value + 0 : null;
$name = $attributes->get('name');

// What names the number goes on its input, so a Field Label's for reaches it.
$labelling = ['id', 'aria-label', 'aria-labelledby'];
@endphp

<eso-number-input {{ $attributes->whereDoesntStartWith('wire:model')->except(['name', ...$labelling])->merge([
    'data-slot' => 'number-input',
    'data-min' => $min ?? false,
    'data-max' => $max ?? false,
    'data-step' => $step,
    'x-data' => 'uiNumberInput('.json_encode(Model::binding($attributes)).')',
    'x-init' => '$nextTick(() => render())',
    'x-effect' => 'render()',
    'x-on:pointerdown' => 'press($event)',
    'x-on:pointerup.window' => 'stop()',
    'x-on:pointercancel.window' => 'stop()',
    'x-on:keydown' => 'key($event)',
    'x-on:input' => 'revision++; render()',
    'x-on:focusout' => '!$el.contains($event.relatedTarget) && blur()',
]) }}>
    <x-ui::input-group :render-error="false">
        <x-ui::input-group.addon>
            <x-ui::button variant="ghost" size="icon-xs" data-number-input="decrement" :aria-label="__('ui::number-input.decrement')" tabindex="-1" :disabled="$disabled || ($min !== null && $value !== null && $value <= $min)">
                <x-ui::icon name="minus" />
            </x-ui::button>
        </x-ui::input-group.addon>
        <x-ui::input-group.input x-ref="input" inputmode="decimal" role="spinbutton" :value="$value ?? ''" :placeholder="$placeholder" autocomplete="off" :name="$name" :aria-valuemin="$min" :aria-valuemax="$max" :aria-valuenow="$value" :state="$state" :disabled="$disabled" :attributes="$attributes->only($labelling)->merge(Model::input($attributes, number: true)->getAttributes())" />
        <x-ui::input-group.addon align="inline-end">
            <x-ui::button variant="ghost" size="icon-xs" data-number-input="increment" :aria-label="__('ui::number-input.increment')" tabindex="-1" :disabled="$disabled || ($max !== null && $value !== null && $value >= $max)">
                <x-ui::icon name="plus" />
            </x-ui::button>
        </x-ui::input-group.addon>
    </x-ui::input-group>
</eso-number-input>
<x-ui::field.error :state="$state" />
<x-ui::extras />
