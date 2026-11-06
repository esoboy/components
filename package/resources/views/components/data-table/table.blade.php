{{-- This view is rendered by the class component that provides entries() to the caller. --}}
@use('Esoboy\Ui\Support\Model')
@use('Illuminate\Support\Js')

@php($owner = Model::component())

<x-ui::data-table.content :sticky-header="$stickyHeader" :attributes="$attributes">
    <x-ui::table.header>
        <x-ui::table.row>
            @if ($tableLayout['selectable'])
                <x-ui::table.head scope="col" class="w-8" sticky="start"><x-ui::data-table.select-all /></x-ui::table.head>
            @endif
            @if ($details)
                <x-ui::table.head scope="col" class="w-8"><span class="sr-only">{{ __('ui::data-table.expand_detail') }}</span></x-ui::table.head>
            @endif
            @foreach ($tableLayout['columns'] as $key => $column)
                @php($sorted = $owner && data_get($owner, 'sortBy') === $key ? data_get($owner, 'sortDirection') : null)
                <x-ui::table.head scope="col" :sticky="$column['fixed'] ?? null" :aria-sort="match ($sorted) {'asc' => 'ascending', 'desc' => 'descending', default => null}">
                    {{ $column['title'] }}
                    @if (($column['sortable'] ?? false) && $owner && method_exists($owner, 'sort'))
                        <x-ui::native-select
                            size="sm"
                            data-data-table-sort
                            :aria-label="$column['title'].' — '.__('ui::data-table.sort')"
                            :wire:change="'sort('.Js::from($key).', $event.target.value)'"
                        >
                            <option value="default" @selected($sorted === null)>{{ __('ui::data-table.default_sort') }}</option>
                            <option value="asc" @selected($sorted === 'asc')>{{ __('ui::data-table.asc') }}</option>
                            <option value="desc" @selected($sorted === 'desc')>{{ __('ui::data-table.desc') }}</option>
                        </x-ui::native-select>
                    @endif
                </x-ui::table.head>
            @endforeach
            @if ($actions)
                <x-ui::table.head scope="col">{{ $actionsTitle }}</x-ui::table.head>
            @endif
        </x-ui::table.row>
    </x-ui::table.header>

    <x-ui::table.body>
        @if ($slot->isEmpty())
            <x-ui::data-table.empty :colspan="$tableLayout['span']" />
        @else
            {{ $slot }}
        @endif
    </x-ui::table.body>
    @isset($summary)
        <x-ui::table.footer>{{ $summary }}</x-ui::table.footer>
    @endisset
</x-ui::data-table.content>
