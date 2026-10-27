<?php

namespace Esoboy\Ui\Livewire;

use Illuminate\Contracts\Database\Query\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Server state for a Data Table, the Livewire counterpart of shadcn's TanStack table
 * features: sorting, page size, row selection and column visibility. The Data Table
 * parts read and change it; the query stays yours.
 */
trait WithDataTable
{
    use WithPagination;

    #[Url(except: null)]
    public ?string $sortBy = null;

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    #[Url(except: 10)]
    public int $perPage = 10;

    /** @var list<int|string> */
    public array $selected = [];

    /** @var list<string> */
    public array $hiddenColumns = [];

    /**
     * Sorts by a column: in the given direction, or ascending then descending.
     */
    public function sort(string $column, ?string $direction = null): void
    {
        $this->sortDirection = $direction ?? ($this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc');
        $this->sortBy = $column;
        $this->resetPage();
    }

    public function toggleColumn(string $column): void
    {
        $this->hiddenColumns = in_array($column, $this->hiddenColumns, true)
            ? array_values(array_diff($this->hiddenColumns, [$column]))
            : [...$this->hiddenColumns, $column];
    }

    public function columnVisible(string $column): bool
    {
        return ! in_array($column, $this->hiddenColumns, true);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Applies the current sort, if it is one of the sortable columns.
     *
     * @template TQuery of Builder
     *
     * @param  TQuery  $query
     * @param  list<string>  $sortable
     * @return TQuery
     */
    protected function sortQuery(Builder $query, array $sortable): Builder
    {
        if ($this->sortBy !== null && in_array($this->sortBy, $sortable, true)) {
            $query->orderBy($this->sortBy, $this->sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query;
    }
}
