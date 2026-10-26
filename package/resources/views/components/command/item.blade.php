@blaze

@props([
    'value' => null,
    'keywords' => [],
])

{{-- Filtering matches the text, the value and any keywords; Enter or a click clicks the item, so wire:click works. --}}
<div {{ $attributes->merge([
    'role' => 'option',
    'data-slot' => 'command-item',
    'data-value' => $value,
    'data-keywords' => $keywords ? implode(' ', (array) $keywords) : false,
    'aria-selected' => 'false',
    'aria-disabled' => $attributes->has('disabled') ? 'true' : false,
]) }}>
    {{ $slot }}
    <svg data-slot="command-item-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
</div>
