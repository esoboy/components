@blaze(fold: true)

@aware(['uiPopoverId' => null])

@props([
    'as' => 'button',
])

@php
$attributes = $attributes->merge(['data-ui-popover' => $uiPopoverId, 'x-data' => 'uiOverlayTrigger', 'x-on:pointerover' => 'enter($event)', 'x-on:pointerout' => 'leave($event)', 'x-on:focusin' => 'enter($event)', 'x-on:focusout' => 'leave($event)', 'x-on:click' => 'click($event)', 'x-on:keydown' => 'key($event)', 'x-on:contextmenu' => 'context($event)', 'data-popover-trigger' => true, 'command' => 'toggle-popover', 'commandfor' => $uiPopoverId]);
@endphp

{{-- as renders another component as the trigger, like shadcn's render (sidebar.menu-button, tooltip.trigger, …); it must render a <button>, whose command the browser runs. --}}
@if ($as === 'button')
<x-ui::button :attributes="$attributes">{{ $slot }}</x-ui::button>
@else
<x-dynamic-component :component="'ui::'.$as" :attributes="$attributes">{{ $slot }}</x-dynamic-component>
@endif
