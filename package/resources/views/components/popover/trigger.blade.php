@blaze

@php
$id = \Esoboy\Ui\Support\Overlay::open('popover');
@endphp

<x-ui::button :attributes="$attributes->merge(['commandfor' => $id, 'command' => 'toggle-popover', 'style' => 'anchor-name: --'.$id])">{{ $slot }}</x-ui::button>
