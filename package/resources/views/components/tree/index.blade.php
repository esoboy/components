@blaze

@use('Esoboy\Ui\Support\Model')

{{-- items: [['value' => ..., 'label' => ..., 'children' => [...], 'disabled' => bool, 'lazy' => bool], ...]. --}}
@props([
    'items' => null,
    'checkable' => false,
    'value' => null,
    'expanded' => [],
    'expandAll' => false,
    'searchable' => false,
    'placeholder' => null,
    'load' => null,
    'draggable' => false,
    'disabled' => false,
])

@php
$value = Model::value($attributes, $value);
$items ??= [];
$settings = compact('checkable', 'expanded', 'expandAll', 'searchable', 'placeholder', 'load', 'draggable', 'disabled');
$forward = $attributes->except('wire:key')->getAttributes();
if (isset($forward['wire:move'])) $forward['wire:move'] = '$parent.'.$forward['wire:move'];
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'tree-'.\Esoboy\Ui\Support\Sequence::next('tree');
@endphp

@livewire('ui.tree', ['items' => $items, 'settings' => $settings, 'value' => $value, ...$forward], key('ui-tree-'.$key))
<x-ui::extras />
