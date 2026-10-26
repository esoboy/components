@blaze

@php
$id = \Esoboy\Ui\Support\Overlay::open('tooltip');
@endphp

<x-ui::button :attributes="$attributes->merge(['data-tooltip' => $id, 'aria-describedby' => $id, 'style' => 'anchor-name: --'.$id])">{{ $slot }}</x-ui::button>
