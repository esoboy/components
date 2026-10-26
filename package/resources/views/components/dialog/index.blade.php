@blaze(fold: true)

{{-- The name is the <dialog> id: triggers point at it and $this->dialog($name) opens it. --}}
@props([
    'name',
])

{{ $slot }}
