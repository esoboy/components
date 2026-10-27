@blaze

@use('Esoboy\Ui\Support\Model')

{{-- items: [['value' => ..., 'label' => ..., 'children' => [...], 'disabled' => bool], ...], or nested <x-ui::tree.item> tags. --}}
@props([
    'items' => null,
    'checkable' => false,
    'defaultValue' => null,
    'defaultExpanded' => [],
    'expandAll' => false,
])

@php
$value = $defaultValue ?? Model::value($attributes);
$value = $checkable ? array_values(array_map('strval', (array) ($value ?? []))) : ($value === null || $value === '' ? null : (string) $value);
@endphp

{{-- <eso-tree> exposes the chosen item (or the checked items, when checkable) as .value, so wire:model binds on it. --}}
<eso-tree {{ $attributes->merge([
    'role' => 'tree',
    'data-slot' => 'tree',
    'data-checkable' => (bool) $checkable,
    'data-value' => json_encode($value),
    'aria-multiselectable' => $checkable ? 'true' : false,
]) }}>
    @if ($items !== null)
    @foreach ($items as $item)
    <x-ui::tree.item :item="$item" />
    @endforeach
    @else
    {{ $slot }}
    @endif
</eso-tree>
