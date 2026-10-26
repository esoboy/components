@blaze

@php
$id = \Esoboy\Ui\Support\Overlay::close('dropdown-menu-sub');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'role' => 'menu',
    'tabindex' => '-1',
    'data-slot' => 'dropdown-menu-sub-content',
    'data-side' => 'right',
    'data-align' => 'start',
    'style' => "position-anchor: --{$id}; --side-offset: 0px",
]) }}>{{ $slot }}</div>
