@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- options: [['value' => ..., 'label' => ..., 'children' => [...], 'disabled' => bool], ...]. The value is the path of values. --}}
@props([
    'options' => [],
    'placeholder' => null,
    'separator' => '/',
    'changeOnSelect' => false,
    'size' => 'default',
    'disabled' => false,
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = array_values(array_map('strval', (array) ($defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes) ?? [])));
$labels = \Esoboy\Ui\Support\CascaderOptions::labels($options, $value);
$id = \Esoboy\Ui\Support\Overlay::open('cascader');
\Esoboy\Ui\Support\Overlay::close('cascader');
@endphp

{{-- <ui-cascader> exposes the chosen path as .value, so wire:model binds on it. --}}
<ui-cascader {{ $attributes->except('name')->merge([
    'data-slot' => 'cascader',
    'data-value' => json_encode($labels ? $value : []),
    'data-separator' => $separator,
    'data-placeholder' => $placeholder ?? false,
    'data-change-on-select' => (bool) $changeOnSelect,
    'data-name' => $attributes->get('name') ?? false,
]) }}>
    <script type="application/json" data-cascader-options>@json(\Esoboy\Ui\Support\CascaderOptions::normalize($options))</script>
    <button type="button" data-slot="select-trigger" data-cascader-trigger data-size="{{ $size }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $id }}" style="anchor-name: --{{ $id }}" {{ new \Illuminate\View\ComponentAttributeBag($state->attributes()) }} @if (! $labels) data-placeholder @endif @if ($disabled) disabled @endif>
        <span data-slot="select-value">{{ $labels ? implode(" {$separator} ", $labels) : $placeholder }}</span>
        <svg data-slot="select-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div id="{{ $id }}" popover="auto" data-slot="cascader-content" data-side="bottom" data-align="start" style="position-anchor: --{{ $id }}; --side-offset: 4px" wire:ignore></div>
    @if ($attributes->has('name'))
        @foreach ($labels ? $value : [] as $part)
        <input type="hidden" name="{{ $attributes->get('name') }}[]" value="{{ $part }}">
        @endforeach
    @endif
</ui-cascader>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
