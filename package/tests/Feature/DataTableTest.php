<?php

/*
 * Data Table: the WithDataTable state and the parts that read it.
 */

use Esoboy\Ui\Tests\Fixtures\Payments;
use Livewire\Livewire;

it('sorts a column ascending, then descending, back on the first page', function () {
    Livewire::test(Payments::class)
        ->call('setPage', 3)
        ->call('sort', 'email')
        ->assertSet('sortBy', 'email')
        ->assertSet('sortDirection', 'asc')
        ->assertSet('paginators.page', 1)
        ->call('sort', 'email')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'email', 'asc')
        ->assertSet('sortDirection', 'asc');
});

it('applies only allowed sorts to the query', function () {
    $component = Livewire::test(Payments::class);

    expect($component->instance()->sortedSql())->not->toContain('order by');

    $component->call('sort', 'email', 'desc');
    expect($component->instance()->sortedSql())->toContain('order by "email" desc');

    $component->call('sort', 'password');
    expect($component->instance()->sortedSql())->not->toContain('order by');
});

it('hides and shows columns', function () {
    Livewire::test(Payments::class)
        ->call('toggleColumn', 'email')
        ->assertSet('hiddenColumns', ['email'])
        ->assertSee('data-slot="dropdown-menu-checkbox-item"', false)
        ->call('toggleColumn', 'email')
        ->assertSet('hiddenColumns', []);
});

it('renders sortable headers with their sort menu', function () {
    $html = Livewire::test(Payments::class)->call('sort', 'email', 'desc')->html();

    expect($html)
        ->toContain('<div data-slot="data-table-column-header">')
        ->toContain('wire:click="sort(\'email\', \'asc\')"')
        ->toContain('wire:click="sort(\'email\', \'desc\')"')
        ->toContain('wire:click="toggleColumn(\'email\')"')
        ->toContain('<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>')
        ->toContain('<div >Status</div>');
});

it('renders the selection boxes bound to the selected rows', function () {
    $html = Livewire::test(Payments::class)->html();

    expect($html)
        ->toContain('<ui-data-table data-slot="data-table">')
        ->toMatch('/data-data-table-select-all="data-data-table-select-all" aria-label="Select all"/')
        ->toMatch('/wire:model.live="selected" value="1" data-data-table-select-row="data-data-table-select-row" aria-label="Select row"/')
        ->toContain('<td data-slot="table-cell" colspan="3" data-data-table-empty="data-data-table-empty">')
        ->toContain('No results.');
});

it('renders the pagination with count, page size and page buttons', function () {
    $html = Livewire::test(Payments::class)->set('selected', [1, 2])->call('setPage', 2)->html();

    expect($html)
        ->toContain('<div data-slot="data-table-selection">2 of 42 row(s) selected.</div>')
        ->toContain('<p>Rows per page</p>')
        ->toContain('wire:model.live="perPage"')
        ->toContain('<div data-slot="data-table-page">Page 2 of 5</div>')
        ->toContain('wire:click="setPage(1, \'page\')"')
        ->toContain('wire:click="previousPage(\'page\')"')
        ->toContain('wire:click="setPage(5, \'page\')"')
        ->toContain('<span class="sr-only">Go to first page</span>');
});

it('resets to the first page when the page size changes', function () {
    Livewire::test(Payments::class)
        ->call('setPage', 4)
        ->set('perPage', 20)
        ->assertSet('paginators.page', 1);
});
