@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'side' => 'bottom',
    'align' => 'start',
    'sideOffset' => 6,
])

@php
$id = Overlay::close('combobox');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'manual',
    'role' => 'listbox',
    'data-slot' => 'combobox-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px;",
]) }}>{{ $slot }}</div>
