@blaze

@props([
    'side' => 'bottom',
    'align' => 'center',
    'sideOffset' => 4,
    'alignItemWithTrigger' => true,
])

@php
$id = \Esoboy\Ui\Support\Overlay::close('select');
@endphp

<div {{ $attributes->merge([
    'id' => $id,
    'popover' => 'auto',
    'role' => 'listbox',
    'tabindex' => '-1',
    'data-slot' => 'select-content',
    'data-align-trigger' => $alignItemWithTrigger ? 'true' : 'false',
    'data-side' => $side,
    'data-align' => $align,
    'style' => "position-anchor: --{$id}; --side-offset: {$sideOffset}px",
]) }}>{{ $slot }}</div>
