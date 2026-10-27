@blaze

{{-- shadcn's DataTablePagination for a Laravel paginator: selection count, rows per page, page x of y, first / previous / next / last. --}}
@props([
    'paginator',
    'pageSizes' => [10, 20, 25, 30, 40, 50],
])

@php
$table = app('view')->shared('__livewire');
$selected = $table && property_exists($table, 'selected') ? count($table->selected) : 0;
$total = method_exists($paginator, 'total') ? $paginator->total() : $paginator->count();
$last = method_exists($paginator, 'lastPage') ? max($paginator->lastPage(), 1) : $paginator->currentPage() + ($paginator->hasMorePages() ? 1 : 0);
$page = $paginator->getPageName();
$perPage = $table && property_exists($table, 'perPage') ? (string) $table->perPage : null;
@endphp

<div {{ $attributes->merge(['data-slot' => 'data-table-pagination']) }}>
    <div data-slot="data-table-selection">{{ __('ui.data_table.selected', ['selected' => $selected, 'total' => $total]) }}</div>
    <div data-slot="data-table-pagination-controls">
        @if ($perPage !== null)
        <div data-slot="data-table-page-size">
            <p>{{ __('ui.data_table.rows_per_page') }}</p>
            <x-ui::select wire:model.live="perPage">
                <x-ui::select.trigger class="h-8 w-[70px]"><x-ui::select.value :placeholder="$perPage" /></x-ui::select.trigger>
                <x-ui::select.content side="top">
                    @foreach ($pageSizes as $size)
                    <x-ui::select.item :value="(string) $size">{{ $size }}</x-ui::select.item>
                    @endforeach
                </x-ui::select.content>
            </x-ui::select>
        </div>
        @endif
        <div data-slot="data-table-page">{{ __('ui.data_table.page', ['current' => $paginator->currentPage(), 'total' => $last]) }}</div>
        <div data-slot="data-table-page-buttons">
            <x-ui::button variant="outline" size="icon" class="hidden size-8 lg:flex" wire:click="setPage(1, '{{ $page }}')" :disabled="$paginator->onFirstPage()">
                <span class="sr-only">{{ __('ui.data_table.first_page') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/></svg>
            </x-ui::button>
            <x-ui::button variant="outline" size="icon" class="size-8" wire:click="previousPage('{{ $page }}')" :disabled="$paginator->onFirstPage()">
                <span class="sr-only">{{ __('ui.pagination.previous_page') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </x-ui::button>
            <x-ui::button variant="outline" size="icon" class="size-8" wire:click="nextPage('{{ $page }}')" :disabled="! $paginator->hasMorePages()">
                <span class="sr-only">{{ __('ui.pagination.next_page') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </x-ui::button>
            <x-ui::button variant="outline" size="icon" class="hidden size-8 lg:flex" wire:click="setPage({{ $last }}, '{{ $page }}')" :disabled="! $paginator->hasMorePages()">
                <span class="sr-only">{{ __('ui.data_table.last_page') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/></svg>
            </x-ui::button>
        </div>
    </div>
</div>
