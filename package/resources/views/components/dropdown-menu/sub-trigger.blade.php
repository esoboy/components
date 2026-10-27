@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'inset' => false,
])

@php
$id = Overlay::open('dropdown-menu-sub');
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'role' => 'menuitem',
    'aria-haspopup' => 'menu',
    'data-slot' => 'dropdown-menu-sub-trigger',
    'data-inset' => $inset,
    'commandfor' => $id,
    'command' => 'toggle-popover',
    'style' => 'anchor-name: --'.$id,
]) }}>
    {{ $slot }}
    <x-ui::icon name="chevron-right" data-slot="dropdown-menu-sub-trigger-icon" />
</button>
