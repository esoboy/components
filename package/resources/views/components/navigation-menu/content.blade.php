@blaze

@aware([
    'align' => 'start',
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('navigation-menu');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'data-slot' => 'navigation-menu-content',
    'data-side' => 'bottom',
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: 8px",
]) }}>{{ $slot }}</div>
