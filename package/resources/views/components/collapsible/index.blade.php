@blaze

@props([
    'defaultOpen' => false,
])

@php
$open = (bool) ($defaultOpen ?: \Esoboy\Ui\Support\Model::value($attributes));
@endphp

{{-- <eso-collapsible> exposes the open state as .value, so wire:model binds on it. --}}
<eso-collapsible {{ $attributes->merge(['data-slot' => 'collapsible', 'data-open' => $open]) }}>{{ $slot }}</eso-collapsible>
