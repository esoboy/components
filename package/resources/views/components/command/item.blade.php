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
    <x-ui::icon name="check" data-slot="command-item-check" />
</div>
