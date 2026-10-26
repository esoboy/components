@blaze(fold: true)

@props([
    'orientation' => 'vertical',
])

<div {{ $attributes->merge(['role' => 'group', 'data-slot' => 'field', 'data-orientation' => $orientation]) }}>{{ $slot }}</div>
