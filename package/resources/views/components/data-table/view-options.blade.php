@blaze

{{-- columns: ['email' => 'Email', ...], the hideable columns and their labels. --}}
@props([
    'columns' => [],
])

@php
$table = app('view')->shared('__livewire');
$hidden = $table && property_exists($table, 'hiddenColumns') ? $table->hiddenColumns : [];
@endphp

<x-ui::dropdown-menu>
    <x-ui::dropdown-menu.trigger variant="outline" size="sm" :attributes="$attributes->merge(['data-slot' => 'data-table-view-options'])">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 17H5"/><path d="M19 7h-9"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
        {{ __('ui.data_table.view') }}
    </x-ui::dropdown-menu.trigger>
    <x-ui::dropdown-menu.content align="end" class="w-[150px]">
        <x-ui::dropdown-menu.label>{{ __('ui.data_table.toggle_columns') }}</x-ui::dropdown-menu.label>
        <x-ui::dropdown-menu.separator />
        @foreach ($columns as $column => $label)
        <x-ui::dropdown-menu.checkbox-item class="capitalize" :checked="! in_array($column, $hidden, true)" wire:click="toggleColumn('{{ $column }}')">{{ $label }}</x-ui::dropdown-menu.checkbox-item>
        @endforeach
    </x-ui::dropdown-menu.content>
</x-ui::dropdown-menu>
