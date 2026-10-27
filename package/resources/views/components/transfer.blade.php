@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: two lists of Checkboxes with Command's search and items; the value is the right list. --}}
@props([
    'options' => [],
    'titles' => [],
    'searchable' => false,
    'disabled' => false,
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$options = \Esoboy\Ui\Support\CascaderOptions::normalize(collect($options)->map(fn ($option, $key) => is_array($option) ? $option : ['value' => $key, 'label' => $option])->values()->all());
$value = array_values(array_map('strval', (array) ($defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes) ?? [])));
$name = $attributes->get('name');
$lists = [
    'source' => ['title' => $titles[0] ?? __('ui.transfer.source'), 'options' => array_filter($options, fn ($option) => ! in_array($option['value'], $value, true))],
    'target' => ['title' => $titles[1] ?? __('ui.transfer.target'), 'options' => array_filter($options, fn ($option) => in_array($option['value'], $value, true))],
];
@endphp

{{-- <ui-transfer> exposes the right list's values as .value, so wire:model binds on it. --}}
<ui-transfer {{ $attributes->except('name')->merge(['data-slot' => 'transfer', 'data-value' => json_encode($value), 'data-name' => $name ?? false, 'data-disabled' => (bool) $disabled, ...$state->attributes()]) }}>
    @foreach ($lists as $list => $data)
    <div role="group" data-slot="transfer-list" data-list="{{ $list }}" aria-label="{{ $data['title'] }}">
        <div data-slot="transfer-header">
            <input type="checkbox" data-slot="checkbox" data-transfer-all aria-label="{{ $data['title'] }}" @disabled($disabled)>
            <span data-slot="transfer-title">{{ $data['title'] }}</span>
            <span data-slot="transfer-count">{{ '0/'.count($data['options']) }}</span>
        </div>
        @if ($searchable)
        <div data-slot="transfer-search">
            <div role="group" data-slot="input-group">
                <input type="search" data-slot="input-group-control" data-transfer-search placeholder="{{ __('ui.transfer.search') }}" aria-label="{{ __('ui.transfer.search') }}" autocomplete="off">
                <div role="group" data-slot="input-group-addon" data-align="inline-start">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>
        </div>
        @endif
        <div data-slot="transfer-items">
            @foreach ($data['options'] as $index => $option)
            <label data-slot="transfer-item" data-value="{{ $option['value'] }}" data-index="{{ $index }}" wire:key="transfer-{{ $option['value'] }}">
                <input type="checkbox" data-slot="checkbox" @disabled($disabled || ! empty($option['disabled']))>
                <span>{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
        <div data-slot="transfer-empty">{{ __('ui.transfer.empty') }}</div>
    </div>
    @if ($list === 'source')
    <div data-slot="transfer-actions">
        <x-ui::button variant="outline" size="icon-sm" data-transfer-move="target" :aria-label="__('ui.transfer.to_target')" disabled>
            <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </x-ui::button>
        <x-ui::button variant="outline" size="icon-sm" data-transfer-move="source" :aria-label="__('ui.transfer.to_source')" disabled>
            <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </x-ui::button>
    </div>
    @endif
    @endforeach
    @if ($name)
        @foreach ($value as $item)
        <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
        @endforeach
    @endif
</ui-transfer>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
