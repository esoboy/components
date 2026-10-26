@blaze

@aware([
    'defaultValue' => null,
])

@props([
    'value',
])

@php
$active = $defaultValue !== null && (string) $defaultValue === (string) $value;
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'role' => 'tab',
    'data-slot' => 'tabs-trigger',
    'data-value' => $value,
    'data-active' => $active,
    'aria-selected' => $active ? 'true' : 'false',
    'tabindex' => $active ? '0' : '-1',
]) }}>{{ $slot }}</button>
