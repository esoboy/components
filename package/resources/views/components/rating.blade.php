@blaze

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
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
$value = is_numeric($value) ? $value + 0 : null;
$name = $attributes->get('name');
$focusable = min($max, max(1, (int) round($value ?? 1)));
$star = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
@endphp

@if ($readonly)
<span {{ $attributes->except('name')->merge(['role' => 'img', 'data-slot' => 'rating', 'data-size' => $size, 'data-readonly' => true, 'aria-label' => __('ui.rating.label', ['value' => $value ?? 0, 'max' => $max])]) }}>
    @foreach (range(1, $max) as $i)
    <span data-slot="rating-item" style="--rating-fill: {{ round(max(0, min(1, ($value ?? 0) - $i + 1)) * 100) }}%">{!! $star !!}{!! $star !!}</span>
    @endforeach
</span>
@else
{{-- <eso-rating> exposes the stars as .value (null when cleared), so wire:model binds on it. --}}
<eso-rating {{ $attributes->except('name')->merge(['role' => 'radiogroup', 'data-slot' => 'rating', 'data-size' => $size, 'data-value' => $value ?? false, 'data-name' => $name ?? false, 'data-disabled' => (bool) $disabled, ...$state->attributes()]) }}>
    @foreach (range(1, $max) as $i)
    <button type="button" role="radio" data-slot="rating-item" data-value="{{ $i }}" aria-checked="{{ $value !== null && (int) round($value) === $i ? 'true' : 'false' }}" aria-label="{{ __('ui.rating.label', ['value' => $i, 'max' => $max]) }}" tabindex="{{ $i === $focusable ? 0 : -1 }}" style="--rating-fill: {{ $value !== null && $i <= $value ? 100 : 0 }}%" @disabled($disabled)>{!! $star !!}{!! $star !!}</button>
    @endforeach
    @if ($name)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
</eso-rating>
@endif
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
