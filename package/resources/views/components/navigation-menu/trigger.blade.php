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
    'style' => 'anchor-name: --'.$id,
]) }}>
    {{ $slot }}
    <svg data-slot="navigation-menu-trigger-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</button>
