@blaze

@props([
    'defaultValue' => null,
    'multiple' => false,
])

{{-- Items are native <details>; <ui-accordion> names them so only one stays open unless multiple. --}}
<ui-accordion {{ $attributes->merge(['data-slot' => 'accordion', 'data-multiple' => $multiple]) }}>{{ $slot }}</ui-accordion>
