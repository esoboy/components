@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- A Select-style trigger opening a Tree; checkable picks several. --}}
@props([
    'items' => [],
    'checkable' => false,
    'placeholder' => null,
    'size' => 'default',
    'disabled' => false,
    'defaultValue' => null,
    'defaultExpanded' => [],
    'expandAll' => false,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
$labels = \Esoboy\Ui\Support\CascaderOptions::find($items, $checkable ? (array) ($value ?? []) : ($value === null ? [] : [$value]));
$id = \Esoboy\Ui\Support\Overlay::open('tree-select');
\Esoboy\Ui\Support\Overlay::close('tree-select');
@endphp

{{-- <eso-tree-select> proxies its tree's .value, so wire:model binds on it. --}}
<eso-tree-select {{ $attributes->except(['name', 'class', 'style'])->merge(['data-slot' => 'tree-select', 'data-placeholder' => $placeholder ?? false, 'data-checkable' => (bool) $checkable]) }}>
    <button {{ $attributes->only(['class', 'style']) }} type="button" data-slot="select-trigger" data-tree-select-trigger data-size="{{ $size }}" aria-haspopup="tree" aria-expanded="false" aria-controls="{{ $id }}" style="anchor-name: --{{ $id }}" {{ new \Illuminate\View\ComponentAttributeBag($state->attributes()) }} @if (! $labels) data-placeholder @endif @if ($disabled) disabled @endif>
        <span data-slot="select-value">{{ $labels ? implode(', ', $labels) : $placeholder }}</span>
        <svg data-slot="select-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div id="{{ $id }}" popover="auto" data-slot="tree-select-content" data-side="bottom" data-align="start" style="position-anchor: --{{ $id }}; --side-offset: 4px" wire:ignore.self>
        <x-ui::tree :items="$items" :checkable="$checkable" :default-value="$value" :default-expanded="$defaultExpanded" :expand-all="$expandAll" />
    </div>
</eso-tree-select>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
