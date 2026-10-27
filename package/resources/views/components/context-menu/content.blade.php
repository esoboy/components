@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::close('dropdown-menu');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'role' => 'menu',
    'tabindex' => '-1',
    'data-slot' => 'context-menu-content',
]) }}>{{ $slot }}</div>
