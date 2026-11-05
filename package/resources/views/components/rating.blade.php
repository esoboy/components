@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: stars in the warning color, a radio group to the keyboard. Clicking the chosen star clears it; readonly shows fractions. --}}
@props([
    'max' => 5,
    'size' => 'default',
    'readonly' => false,
    'disabled' => false,
    'value' => null,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value);
$value = is_numeric($value) ? $value + 0 : null;
$name = $attributes->get('name');
$focusable = min($max, max(1, (int) round($value ?? 1)));
@endphp

@if ($readonly)
<span {{ $attributes->except('name')->merge(['role' => 'img', 'data-slot' => 'rating', 'data-size' => $size, 'data-readonly' => true, 'aria-label' => __('ui::rating.label', ['value' => $value ?? 0, 'max' => $max])]) }}>
    @foreach (range(1, $max) as $i)
    <span data-slot="rating-item" style="--rating-fill: {{ round(max(0, min(1, ($value ?? 0) - $i + 1)) * 100) }}%"><x-ui::icon name="star" fill="currentColor" stroke="none" /><x-ui::icon name="star" fill="currentColor" stroke="none" /></span>
    @endforeach
</span>
@else
<eso-rating {{ $attributes->whereDoesntStartWith('wire:model')->except('name')->merge(['role' => 'radiogroup', 'data-slot' => 'rating', 'data-size' => $size, 'data-value' => $value ?? false, 'data-name' => $name ?? false, 'data-disabled' => (bool) $disabled, 'x-data' => 'uiRating('.json_encode(Model::binding($attributes)).')', 'x-init' => '$nextTick(() => render())', 'x-effect' => 'render()', 'x-on:focusout' => '!$el.contains($event.relatedTarget) && blur()', 'x-on:click' => 'choose($event.target.closest(\'[data-slot="rating-item"]\'))', 'x-on:keydown' => 'key($event)', ...$state->attributes()]) }}>
    @foreach (range(1, $max) as $i)
    <button type="button" role="radio" data-slot="rating-item" data-value="{{ $i }}" aria-checked="{{ $value !== null && (int) round($value) === $i ? 'true' : 'false' }}" aria-label="{{ __('ui::rating.label', ['value' => $i, 'max' => $max]) }}" tabindex="{{ $i === $focusable ? 0 : -1 }}" style="--rating-fill: {{ $value !== null && $i <= $value ? 100 : 0 }}%" @disabled($disabled)><x-ui::icon name="star" fill="currentColor" stroke="none" /><x-ui::icon name="star" fill="currentColor" stroke="none" /></button>
    @endforeach
    <input type="hidden" x-ref="input" @if ($name) name="{{ $name }}" @endif value="{{ $value }}" {{ Model::input($attributes, number: true) }}>
</eso-rating>
@endif
<x-ui::field.error :state="$state" />
<x-ui::extras />
