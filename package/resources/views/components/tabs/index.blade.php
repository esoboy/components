@blaze

@props([
    'defaultValue' => null,
    'orientation' => 'horizontal',
])

@php
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
@endphp

{{-- <ui-tabs> exposes the active tab as .value, so wire:model binds on the tabs themselves. --}}
<ui-tabs {{ $attributes->merge(['data-slot' => 'tabs', 'data-orientation' => $orientation, 'data-value' => is_scalar($value) ? (string) $value : false]) }}>{{ $slot }}</ui-tabs>
