@blaze

@aware([
    'defaultValue' => null,
])

@props([
    'value',
])

<div {{ $attributes->merge([
    'role' => 'tabpanel',
    'tabindex' => '0',
    'data-slot' => 'tabs-content',
    'data-value' => $value,
    'hidden' => $defaultValue === null || (string) $defaultValue !== (string) $value,
]) }}>{{ $slot }}</div>
