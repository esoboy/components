@blaze(fold: true)

@aware(['uiHoverId' => null])

@props([
    'as' => 'button',
    'delay' => 600,
    'closeDelay' => 300,
])

@php
// Hovering or focusing it opens the Hover Card Content after it, after the delay (ui.js).
$attributes = $attributes->merge(['data-ui-hover-card' => $uiHoverId, 'x-data' => 'uiOverlayTrigger', 'x-on:pointerover' => 'enter($event)', 'x-on:pointerout' => 'leave($event)', 'x-on:focusin' => 'enter($event)', 'x-on:focusout' => 'leave($event)', 'x-on:click' => 'click($event)', 'x-on:keydown' => 'key($event)', 'x-on:contextmenu' => 'context($event)', 'data-hover-card-trigger' => true, 'data-delay' => $delay, 'data-close-delay' => $closeDelay]);
@endphp

{{-- A Button (a link with href) unless as names another component, like shadcn's render. --}}
@if ($as === 'button')
<x-ui::button :attributes="$attributes">{{ $slot }}</x-ui::button>
@else
<x-dynamic-component :component="'ui::'.$as" :attributes="$attributes">{{ $slot }}</x-dynamic-component>
@endif
