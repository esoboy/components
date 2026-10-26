@blaze

@props([
    'side' => 'bottom',
    'align' => 'center',
    'sideOffset' => 4,
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('hover-card');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'manual',
    'data-slot' => 'hover-card-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px",
]) }}>{{ $slot }}</div>
