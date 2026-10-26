@blaze

@props([
    'inset' => false,
])

@php
$id = \Esoboy\Ui\Support\Overlay::open('dropdown-menu-sub');
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
    <svg data-slot="dropdown-menu-sub-trigger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
</button>
