@blaze

@props([
    'filter' => true,
])

{{-- <eso-command> filters the items as you type and runs the highlighted one on Enter. --}}
<eso-command {{ $attributes->merge(['data-slot' => 'command', 'data-filter' => $filter ? 'true' : 'false', 'x-data' => 'uiCommand', 'x-on:input' => 'refresh()', 'x-on:keydown' => 'key($event)', 'x-on:pointermove' => 'hover($event)']) }}>{{ $slot }}</eso-command>
