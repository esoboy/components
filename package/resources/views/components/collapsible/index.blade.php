@blaze

@props([
    'defaultOpen' => false,
])

@php
$open = (bool) ($defaultOpen ?: \Esoboy\Ui\Support\Model::value($attributes));
@endphp

{{-- <ui-collapsible> exposes the open state as .value, so wire:model binds on it. --}}
<ui-collapsible {{ $attributes->merge(['data-slot' => 'collapsible', 'data-open' => $open]) }}>{{ $slot }}</ui-collapsible>
