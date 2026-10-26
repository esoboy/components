@blaze

@props([
    'side' => 'bottom',
    'align' => 'start',
    'sideOffset' => 8,
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('dropdown-menu');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'role' => 'menu',
    'tabindex' => '-1',
    'data-slot' => 'menubar-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px",
]) }}>{{ $slot }}</div>
