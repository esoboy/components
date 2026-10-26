@blaze

@props([
    'shouldFilter' => true,
])

{{-- <ui-command> filters the items as you type and runs the highlighted one on Enter. --}}
<ui-command {{ $attributes->merge(['data-slot' => 'command', 'data-should-filter' => $shouldFilter ? 'true' : 'false']) }}>{{ $slot }}</ui-command>
