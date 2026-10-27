@blaze

@use('Esoboy\Ui\Support\Model')

{{-- columns: ['email' => 'Email', ...], the hideable columns and their labels. --}}
@props([
    'columns' => [],
])

@php
$table = Model::component();
$hidden = $table && property_exists($table, 'hiddenColumns') ? $table->hiddenColumns : [];
@endphp

<x-ui::dropdown-menu>
    <x-ui::dropdown-menu.trigger variant="outline" size="sm" :attributes="$attributes->merge(['data-data-table-view-options' => true])">
        <x-ui::icon name="settings-2" />
        {{ __('ui::data-table.view') }}
    </x-ui::dropdown-menu.trigger>
    <x-ui::dropdown-menu.content align="end" class="w-[150px]">
        <x-ui::dropdown-menu.label>{{ __('ui::data-table.toggle_columns') }}</x-ui::dropdown-menu.label>
        <x-ui::dropdown-menu.separator />
        @foreach ($columns as $column => $label)
        <x-ui::dropdown-menu.checkbox-item class="capitalize" :checked="! in_array($column, $hidden, true)" wire:click="toggleColumn('{{ $column }}')">{{ $label }}</x-ui::dropdown-menu.checkbox-item>
        @endforeach
    </x-ui::dropdown-menu.content>
</x-ui::dropdown-menu>
