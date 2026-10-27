@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('dropdown-menu');
@endphp

{{-- Right-clicking the area opens the menu at the pointer. --}}
<div {{ $attributes->merge(['data-slot' => 'context-menu-trigger', 'data-context-menu' => $id]) }}>{{ $slot }}</div>
