@blaze

@use('Esoboy\Ui\Support\EditorControls')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'format' => 'full',
    'tools' => null,
])

{{-- Any control by name, when the format and tools have it: a button, a menu or a popover. --}}
@props([
    'name',
])

@php
$rich = RichText::resolve($format);
@endphp

@if (EditorControls::allowed($name, $rich, $rich->tools($tools)))
@switch($name)
@case('heading')
@case('font-family')
@case('font-size')
@case('text-align')
@case('line-height')
@case('callout')
<x-ui::editor.menu :name="$name" :attributes="$attributes" />
@break
@case('color')
<x-ui::editor.color :attributes="$attributes" />
@break
@case('spacing')
<x-ui::editor.spacing :attributes="$attributes" />
@break
@case('link')
<x-ui::editor.link :attributes="$attributes" />
@break
@case('embed')
<x-ui::editor.embed :attributes="$attributes" />
@break
@case('math')
<x-ui::editor.math :attributes="$attributes" />
@break
@case('emoji')
<x-ui::editor.emoji :attributes="$attributes" />
@break
@default
<x-ui::editor.button :command="$name" :attributes="$attributes" />
@endswitch
@endif
