@blaze

@props([
    'side' => 'top',
    'align' => 'center',
    'sideOffset' => 0,
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('tooltip');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'manual',
    'role' => 'tooltip',
    'data-slot' => 'tooltip-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px",
]) }}>{{ $slot }}</div>
