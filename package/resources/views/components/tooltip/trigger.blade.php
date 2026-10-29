@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('tooltip');
@endphp

<x-ui::button :attributes="$attributes->merge(['data-tooltip' => $id, 'aria-describedby' => $id, 'style' => 'anchor-name: --'.$id.';'])">{{ $slot }}</x-ui::button>
