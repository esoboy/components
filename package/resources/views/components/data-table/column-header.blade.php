@blaze

@use('Esoboy\Ui\Support\Model')

@props([
    'column',
    'title',
    'sortable' => true,
    'hideable' => true,
])

@php
// Reads the sort from the Livewire component using WithDataTable, like TanStack's column state.
$table = Model::component();
$canSort = $sortable && $table && method_exists($table, 'sort');
$sorted = $canSort && $table->sortBy === $column ? $table->sortDirection : null;
$canHide = $hideable && $table && method_exists($table, 'toggleColumn');
@endphp

@if (! $canSort)
<div {{ $attributes }}>{{ $title }}</div>
@else
<div {{ $attributes->merge(['data-slot' => 'data-table-column-header']) }}>
    <x-ui::dropdown-menu>
        <x-ui::dropdown-menu.trigger variant="ghost" size="sm">
            <span>{{ $title }}</span>
            @if ($sorted === 'desc')
            <x-ui::icon name="arrow-down" />
            @elseif ($sorted === 'asc')
            <x-ui::icon name="arrow-up" />
            @else
            <x-ui::icon name="chevrons-up-down" />
            @endif
        </x-ui::dropdown-menu.trigger>
        <x-ui::dropdown-menu.content align="start">
            <x-ui::dropdown-menu.item wire:click="sort('{{ $column }}', 'asc')"><x-ui::icon name="arrow-up" />{{ __('ui::data-table.asc') }}</x-ui::dropdown-menu.item>
            <x-ui::dropdown-menu.item wire:click="sort('{{ $column }}', 'desc')"><x-ui::icon name="arrow-down" />{{ __('ui::data-table.desc') }}</x-ui::dropdown-menu.item>
            @if ($canHide)
            <x-ui::dropdown-menu.separator />
            <x-ui::dropdown-menu.item wire:click="toggleColumn('{{ $column }}')"><x-ui::icon name="eye-off" />{{ __('ui::data-table.hide') }}</x-ui::dropdown-menu.item>
            @endif
        </x-ui::dropdown-menu.content>
    </x-ui::dropdown-menu>
</div>
@endif
