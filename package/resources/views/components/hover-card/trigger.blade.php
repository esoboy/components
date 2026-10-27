@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'delay' => 600,
    'closeDelay' => 300,
])

@php
$id = Overlay::open('hover-card');
@endphp

{{-- A Button (a link with href); hovering or focusing it opens the card after the delay. --}}
<x-ui::button :attributes="$attributes->merge(['data-hover-card' => $id, 'data-delay' => $delay, 'data-close-delay' => $closeDelay, 'style' => 'anchor-name: --'.$id])">{{ $slot }}</x-ui::button>
