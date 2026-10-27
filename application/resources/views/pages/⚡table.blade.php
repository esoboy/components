<?php

use App\Models\User;
use Esoboy\Ui\Livewire\WithDataTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Esoboy\Ui\Facades\Ui;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        $query = User::query()->when($this->search, fn ($query) => $query->where('email', 'like', "%{$this->search}%"));

        return $this->sortQuery($query, ['name', 'email', 'created_at'])->paginate($this->perPage);
    }

    public function deleteSelected(): void
    {
        $count = count($this->selected);
        $this->selected = [];
        Ui::toast("{$count} users would be deleted", type: 'warning');
    }
};
?>

@php
$more = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>';
@endphp

<div class="mx-auto max-w-4xl p-10">
    <x-ui::data-table>
        <x-ui::data-table.toolbar>
            <x-ui::input wire:model.live.debounce.300ms="search" placeholder="Filter emails..." class="max-w-sm" />
            @if ($selected)
            <x-ui::button variant="destructive" size="sm" wire:click="deleteSelected">Delete {{ count($selected) }}</x-ui::button>
            @endif
            <x-ui::data-table.view-options :columns="['name' => 'Name', 'email' => 'Email', 'status' => 'Status', 'created_at' => 'Joined']" />
        </x-ui::data-table.toolbar>

        <x-ui::data-table.content>
            <x-ui::table.header>
                <x-ui::table.row>
                    <x-ui::table.head><x-ui::data-table.select-all /></x-ui::table.head>
                    @if ($this->columnVisible('name'))
                    <x-ui::table.head><x-ui::data-table.column-header column="name" title="Name" /></x-ui::table.head>
                    @endif
                    @if ($this->columnVisible('email'))
                    <x-ui::table.head><x-ui::data-table.column-header column="email" title="Email" /></x-ui::table.head>
                    @endif
                    @if ($this->columnVisible('status'))
                    <x-ui::table.head><x-ui::data-table.column-header column="status" title="Status" :sortable="false" /></x-ui::table.head>
                    @endif
                    @if ($this->columnVisible('created_at'))
                    <x-ui::table.head class="text-right"><x-ui::data-table.column-header column="created_at" title="Joined" class="justify-end" /></x-ui::table.head>
                    @endif
                    <x-ui::table.head />
                </x-ui::table.row>
            </x-ui::table.header>
            <x-ui::table.body>
                @forelse ($this->users as $user)
                <x-ui::table.row wire:key="user-{{ $user->id }}">
                    <x-ui::table.cell><x-ui::data-table.select-row :value="$user->id" /></x-ui::table.cell>
                    @if ($this->columnVisible('name'))
                    <x-ui::table.cell>{{ $user->name }}</x-ui::table.cell>
                    @endif
                    @if ($this->columnVisible('email'))
                    <x-ui::table.cell class="lowercase">{{ $user->email }}</x-ui::table.cell>
                    @endif
                    @if ($this->columnVisible('status'))
                    <x-ui::table.cell>
                        <x-ui::badge :variant="$user->email_verified_at ? 'success' : 'warning'">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</x-ui::badge>
                    </x-ui::table.cell>
                    @endif
                    @if ($this->columnVisible('created_at'))
                    <x-ui::table.cell class="text-right">{{ $user->created_at->format('M j, Y') }}</x-ui::table.cell>
                    @endif
                    <x-ui::table.cell>
                        <x-ui::dropdown-menu>
                            <x-ui::dropdown-menu.trigger variant="ghost" size="icon" class="size-8">{!! $more !!}<span class="sr-only">Open menu</span></x-ui::dropdown-menu.trigger>
                            <x-ui::dropdown-menu.content align="end" class="w-40">
                                <x-ui::dropdown-menu.label>Actions</x-ui::dropdown-menu.label>
                                <x-ui::dropdown-menu.item>Copy user ID</x-ui::dropdown-menu.item>
                                <x-ui::dropdown-menu.separator />
                                <x-ui::dropdown-menu.item>View user</x-ui::dropdown-menu.item>
                            </x-ui::dropdown-menu.content>
                        </x-ui::dropdown-menu>
                    </x-ui::table.cell>
                </x-ui::table.row>
                @empty
                <x-ui::data-table.empty colspan="6" />
                @endforelse
            </x-ui::table.body>
        </x-ui::data-table.content>

        <x-ui::data-table.pagination :paginator="$this->users" />
    </x-ui::data-table>
</div>
