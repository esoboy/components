@blaze

@props([
    'showTrigger' => true,
    'showClear' => false,
])

@php
$id = \Esoboy\Ui\Support\Overlay::open('combobox');
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </x-ui::button>
        @endif
        @if ($showClear)
            <x-ui::button variant="ghost" size="icon-xs" data-combobox-clear :aria-label="__('ui.clear')" :disabled="$attributes->has('disabled')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </x-ui::button>
        @endif
    </div>
</div>
