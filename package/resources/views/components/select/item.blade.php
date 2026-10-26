@blaze

@props([
    'value',
])

<div {{ $attributes->merge([
    'role' => 'option',
    'tabindex' => '-1',
    'data-slot' => 'select-item',
    'data-value' => $value,
    'aria-selected' => 'false',
    'aria-disabled' => $attributes->has('disabled') ? 'true' : false,
]) }}>
    <span data-slot="select-item-text">{{ $slot }}</span>
    <span data-slot="select-item-indicator"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span>
</div>
