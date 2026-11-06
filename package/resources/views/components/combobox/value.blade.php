@blaze(fold: true)

@props([
    'placeholder' => null,
])

<span {{ $attributes->merge(['data-slot' => 'combobox-value', 'data-placeholder' => $placeholder ?? false]) }}>{{ $slot->isEmpty() ? $placeholder : $slot }}</span>
