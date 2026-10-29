@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'side' => 'bottom',
    'align' => 'start',
    'sideOffset' => 8,
])

@php
$id = Overlay::close('dropdown-menu');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'role' => 'menu',
    'tabindex' => '-1',
    'data-slot' => 'menubar-content',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px;",
]) }}>{{ $slot }}</div>
