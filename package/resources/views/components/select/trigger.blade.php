@blaze

@props([
    'size' => 'default',
])

@php
$id = \Esoboy\Ui\Support\Overlay::open('select');
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'data-slot' => 'select-trigger',
    'data-size' => $size,
    'aria-haspopup' => 'listbox',
    'aria-expanded' => 'false',
    'aria-controls' => $id,
    'style' => 'anchor-name: --'.$id,
]) }}>
    {{ $slot }}
    <svg data-slot="select-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
</button>
