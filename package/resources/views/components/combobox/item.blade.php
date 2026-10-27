@blaze

@props([
    'value',
])

<div {{ $attributes->merge([
    'role' => 'option',
    'data-slot' => 'combobox-item',
    'data-value' => $value,
    'aria-selected' => 'false',
    'aria-disabled' => $attributes->has('disabled') ? 'true' : false,
]) }}>
    {{ $slot }}
    <span data-slot="combobox-item-indicator"><x-ui::icon name="check" /></span>
</div>
