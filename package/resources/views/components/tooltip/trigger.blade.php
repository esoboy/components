@blaze(fold: true)

@aware(['uiTooltipId' => null])

@props([
    'as' => 'button',
])

@php
// Hovering or focusing it shows the Tooltip Content after it (ui.js).
$attributes = $attributes->merge(['data-ui-tooltip' => $uiTooltipId, 'x-data' => 'uiOverlayTrigger', 'x-on:pointerover' => 'enter($event)', 'x-on:pointerout' => 'leave($event)', 'x-on:focusin' => 'enter($event)', 'x-on:focusout' => 'leave($event)', 'x-on:click' => 'click($event)', 'x-on:keydown' => 'key($event)', 'x-on:contextmenu' => 'context($event)', 'data-tooltip-trigger' => true]);
@endphp

{{-- as renders another component as the trigger, like shadcn's render: dropdown-menu.trigger, input-group.button, … --}}
@if ($as === 'button')
<x-ui::button :attributes="$attributes">{{ $slot }}</x-ui::button>
@else
<x-dynamic-component :component="'ui::'.$as" :attributes="$attributes">{{ $slot }}</x-dynamic-component>
@endif
