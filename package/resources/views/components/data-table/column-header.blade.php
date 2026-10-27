@blaze

@props([
    'column',
    'title',
    'sortable' => true,
    'hideable' => true,
])

@php
// Reads the sort from the Livewire component using WithDataTable, like TanStack's column state.
$table = app('view')->shared('__livewire');
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
            @elseif ($sorted === 'asc')
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
            @else
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
            @endif
        </x-ui::dropdown-menu.trigger>
        <x-ui::dropdown-menu.content align="start">
            <x-ui::dropdown-menu.item wire:click="sort('{{ $column }}', 'asc')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>{{ __('ui.data_table.asc') }}</x-ui::dropdown-menu.item>
            <x-ui::dropdown-menu.item wire:click="sort('{{ $column }}', 'desc')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>{{ __('ui.data_table.desc') }}</x-ui::dropdown-menu.item>
            @if ($canHide)
            <x-ui::dropdown-menu.separator />
            <x-ui::dropdown-menu.item wire:click="toggleColumn('{{ $column }}')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>{{ __('ui.data_table.hide') }}</x-ui::dropdown-menu.item>
            @endif
        </x-ui::dropdown-menu.content>
    </x-ui::dropdown-menu>
</div>
@endif
