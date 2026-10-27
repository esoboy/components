@blaze

{{-- value is what is inserted after the prefix; prefix keeps the item to one prefix when there are several. --}}
@props([
    'value',
    'prefix' => null,
])

<div {{ $attributes->merge([
    'role' => 'option',
    'data-slot' => 'mentions-item',
    'data-value' => $value,
    'data-prefix' => $prefix ?? false,
    'aria-selected' => 'false',
    'aria-disabled' => $attributes->has('disabled') ? 'true' : false,
]) }}>{{ $slot }}</div>
