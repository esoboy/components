@blaze

@props([
    'side' => 'bottom',
    'align' => 'center',
    'sideOffset' => 4,
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('popover');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'data-slot' => 'popover-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px",
]) }}>{{ $slot }}</div>
