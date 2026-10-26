@blaze

@props([
    'variant' => 'default',
    'inset' => false,
])

@php
// Choosing an item closes the whole menu, submenus included.
$menu = \Esoboy\Ui\Support\Overlay::current('dropdown-menu');

$attributes = $attributes->merge([
    'role' => 'menuitem',
    'data-slot' => 'dropdown-menu-item',
    'data-variant' => $variant,
    'data-inset' => $inset,
]);
@endphp

@if ($attributes->has('href'))
<a {{ $attributes }}>{{ $slot }}</a>
@else
<button {{ $attributes->merge(['type' => 'button', 'commandfor' => $menu, 'command' => 'hide-popover']) }}>{{ $slot }}</button>
@endif
