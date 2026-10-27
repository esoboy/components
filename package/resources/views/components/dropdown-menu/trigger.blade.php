@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('dropdown-menu');
@endphp

<x-ui::button :attributes="$attributes->merge(['commandfor' => $id, 'command' => 'toggle-popover', 'aria-haspopup' => 'menu', 'style' => 'anchor-name: --'.$id])">{{ $slot }}</x-ui::button>
