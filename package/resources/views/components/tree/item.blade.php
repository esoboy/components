@blaze

@aware([
    'checkable' => false,
    'defaultExpanded' => [],
    'expandAll' => false,
])

{{-- An item: value and label (or a label slot), an optional icon slot, children as nested items or from item's children. --}}
@props([
    'value' => null,
    'label' => null,
    'item' => null,
    'disabled' => false,
])

@php
if ($item !== null) {
    $value = $item['value'];
    $label = $item['label'] ?? $item['value'];
    $disabled = $item['disabled'] ?? false;
}

$children = $item['children'] ?? [];
$parent = $item !== null ? $children !== [] : $slot->isNotEmpty();
$expanded = $parent && ($expandAll || in_array((string) $value, array_map('strval', (array) $defaultExpanded), true));
@endphp

<li {{ $attributes->merge([
    'role' => 'treeitem',
    'tabindex' => '-1',
    'data-slot' => 'tree-item',
    'data-value' => $value,
    'aria-expanded' => $parent ? ($expanded ? 'true' : 'false') : false,
    'aria-disabled' => $disabled ? 'true' : false,
]) }}>
    <div data-slot="tree-item-row">
        <span data-slot="tree-item-toggle" aria-hidden="true">@if ($parent)<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>@endif</span>
        @if ($checkable)
        <input type="checkbox" data-slot="checkbox" data-tree-check tabindex="-1" aria-hidden="true" @if ($disabled) disabled @endif>
        @endif
        @isset ($icon)
        <span data-slot="tree-item-icon">{{ $icon }}</span>
        @endisset
        <span data-slot="tree-item-label">{{ $label ?? '' }}</span>
    </div>
    @if ($parent)
    <ul role="group" data-slot="tree-group" @if (! $expanded) hidden @endif>
        @if ($item !== null)
        @foreach ($children as $child)
        <x-ui::tree.item :item="$child" />
        @endforeach
        @else
        {{ $slot }}
        @endif
    </ul>
    @endif
</li>
