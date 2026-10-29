@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'size' => 'default',
])

@php
$id = Overlay::open('select');
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'data-slot' => 'select-trigger',
    'data-size' => $size,
    'aria-haspopup' => 'listbox',
    'aria-expanded' => 'false',
    'aria-controls' => $id,
    'style' => 'anchor-name: --'.$id.';',
]) }}>
    {{ $slot }}
    <x-ui::icon name="chevron-down" data-slot="select-icon" />
</button>
