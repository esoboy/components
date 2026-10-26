@blaze

@props([
    'orientation' => 'horizontal',
])

{{-- <ui-resizable-panel-group> sizes its panels and moves them with its handles. --}}
<ui-resizable-panel-group {{ $attributes->merge(['data-slot' => 'resizable-panel-group', 'aria-orientation' => $orientation, 'data-orientation' => $orientation]) }}>{{ $slot }}</ui-resizable-panel-group>
