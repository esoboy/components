@blaze

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
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
$value = is_numeric($value) ? $value + 0 : null;
$name = $attributes->get('name');
@endphp

{{-- <eso-number-input> exposes the number as .value (null when empty), so wire:model binds on it. --}}
<eso-number-input {{ $attributes->except('name')->merge([
    'data-slot' => 'number-input',
    'data-min' => $min ?? false,
    'data-max' => $max ?? false,
    'data-step' => $step,
]) }}>
    <div role="group" data-slot="input-group">
        <div role="group" data-slot="input-group-addon" data-align="inline-start">
            <x-ui::button variant="ghost" size="icon-xs" data-number-input="decrement" :aria-label="__('ui::number-input.decrement')" tabindex="-1" :disabled="$disabled || ($min !== null && $value !== null && $value <= $min)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/></svg>
            </x-ui::button>
        </div>
        <input type="text" inputmode="decimal" role="spinbutton" data-slot="input-group-control" value="{{ $value }}" placeholder="{{ $placeholder }}" autocomplete="off" @if ($name) name="{{ $name }}" @endif @if ($min !== null) aria-valuemin="{{ $min }}" @endif @if ($max !== null) aria-valuemax="{{ $max }}" @endif @if ($value !== null) aria-valuenow="{{ $value }}" @endif {{ new \Illuminate\View\ComponentAttributeBag($state->attributes()) }} @if ($disabled) disabled @endif>
        <div role="group" data-slot="input-group-addon" data-align="inline-end">
            <x-ui::button variant="ghost" size="icon-xs" data-number-input="increment" :aria-label="__('ui::number-input.increment')" tabindex="-1" :disabled="$disabled || ($max !== null && $value !== null && $value >= $max)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            </x-ui::button>
        </div>
    </div>
</eso-number-input>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
