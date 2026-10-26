@blaze(fold: true, unsafe: ['href'])

@props([
    'variant' => 'default',
])

@php
$attributes = $attributes->merge(['data-slot' => 'badge', 'data-variant' => $variant]);
@endphp

@if ($attributes->has('href'))
<a {{ $attributes }}>{{ $slot }}</a>
@else
<span {{ $attributes }}>{{ $slot }}</span>
@endif
