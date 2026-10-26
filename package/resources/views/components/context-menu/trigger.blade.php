@blaze

@php
$id = \Esoboy\Ui\Support\Overlay::open('dropdown-menu');
@endphp

{{-- Right-clicking the area opens the menu at the pointer. --}}
<div {{ $attributes->merge(['data-slot' => 'context-menu-trigger', 'data-context-menu' => $id]) }}>{{ $slot }}</div>
