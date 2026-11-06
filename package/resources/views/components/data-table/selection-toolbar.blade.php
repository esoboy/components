@blaze

@use('Esoboy\Ui\Support\Model')
@use('Illuminate\Support\Js')

@aware(['selectable' => false, 'selection' => 'selected'])

@php($selected = $selectable ? data_get(Model::component(), $selection, []) : [])

@if ($selected)
<div {{ $attributes->merge(['data-slot' => 'data-table-selection-toolbar', 'role' => 'toolbar', 'aria-label' => __('ui::data-table.selection')]) }}>
    <span data-slot="data-table-selection-count" aria-live="polite" class="text-sm tabular-nums">{{ __('ui::data-table.selected_count', ['count' => count($selected)]) }}</span>
    <x-ui::button variant="outline" size="icon-sm" :aria-label="__('ui::data-table.clear_selection')" :wire:click="'clearSelection('.Js::from($selection).')'">
        <x-ui::icon name="x" />
    </x-ui::button>
    {{ $slot }}
</div>
@endif
