@blaze

@use('Esoboy\Ui\Support\Overlay')

@props([
    'showTrigger' => true,
    'showClear' => false,
])

@php
$id = Overlay::open('combobox');
@endphp

<div {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'input-group', 'role' => 'group', 'data-combobox-anchor' => true, 'style' => 'anchor-name: --'.$id]) }}>
    <input {{ $attributes->except(['class', 'style'])->merge([
        'type' => 'text',
        'data-slot' => 'input-group-control',
        'data-combobox-input' => true,
        'role' => 'combobox',
        'autocomplete' => 'off',
        'aria-autocomplete' => 'list',
        'aria-expanded' => 'false',
        'aria-controls' => $id,
    ]) }}>
    <div data-slot="input-group-addon" data-align="inline-end" role="group">
        @if ($showTrigger)
            <x-ui::button variant="ghost" size="icon-xs" data-combobox-trigger tabindex="-1" :aria-controls="$id" :disabled="$attributes->has('disabled')">
                <x-ui::icon name="chevron-down" />
            </x-ui::button>
        @endif
        @if ($showClear)
            <x-ui::button variant="ghost" size="icon-xs" data-combobox-clear :aria-label="__('ui::combobox.clear')" :disabled="$attributes->has('disabled')">
                <x-ui::icon name="x" />
            </x-ui::button>
        @endif
    </div>
</div>
