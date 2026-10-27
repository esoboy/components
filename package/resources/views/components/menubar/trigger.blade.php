@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('dropdown-menu');
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'role' => 'menuitem',
    'aria-haspopup' => 'menu',
    'data-slot' => 'menubar-trigger',
    'commandfor' => $id,
    'command' => 'toggle-popover',
    'style' => 'anchor-name: --'.$id,
]) }}>{{ $slot }}</button>
