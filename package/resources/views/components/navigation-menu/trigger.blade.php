@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('navigation-menu');
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'aria-expanded' => 'false',
    'aria-controls' => $id,
    'data-slot' => 'navigation-menu-trigger',
    'data-navigation-menu' => $id,
    'style' => 'anchor-name: --'.$id.';',
]) }}>
    {{ $slot }}
    <x-ui::icon name="chevron-down" data-slot="navigation-menu-trigger-icon" />
</button>
