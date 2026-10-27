<?php

use App\Models\User;
use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Livewire\WithDataTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    #[Url]
    public string $search = '';

    public int $step = 2;

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

    public function next(): void
    {
        $this->step = min(4, $this->step + 1);
    }
};
?>

<div class="mx-auto max-w-4xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Data Table</h2>
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
                                <x-ui::dropdown-menu.trigger variant="ghost" size="icon" class="size-8"><x-ui::icon name="ellipsis" /><span class="sr-only">Open menu</span></x-ui::dropdown-menu.trigger>
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
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Description List</h2>
        <x-ui::description-list :columns="3">
            <x-ui::description-list.item><x-ui::description-list.term>Order</x-ui::description-list.term><x-ui::description-list.details>#4189</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Status</x-ui::description-list.term><x-ui::description-list.details><x-ui::badge variant="success">Paid</x-ui::badge></x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Total</x-ui::description-list.term><x-ui::description-list.details>$316.00</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item :span="3"><x-ui::description-list.term>Shipping address</x-ui::description-list.term><x-ui::description-list.details>100 Market St, Suite 300, San Francisco, CA 94105</x-ui::description-list.details></x-ui::description-list.item>
        </x-ui::description-list>

        <x-ui::description-list orientation="horizontal" class="max-w-lg">
            <x-ui::description-list.item><x-ui::description-list.term>Full name</x-ui::description-list.term><x-ui::description-list.details>Margot Foster</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Email</x-ui::description-list.term><x-ui::description-list.details>margot@example.com</x-ui::description-list.details></x-ui::description-list.item>
            <x-ui::description-list.item><x-ui::description-list.term>Role</x-ui::description-list.term><x-ui::description-list.details>Backend Developer</x-ui::description-list.details></x-ui::description-list.item>
        </x-ui::description-list>
    </section>

    <section class="space-y-6">
        <h2 class="text-lg font-semibold">Steps</h2>
        <x-ui::steps :current="$step">
            <x-ui::steps.item title="Account" description="Create your account" />
            <x-ui::steps.item title="Shop" description="Set up your store" />
            <x-ui::steps.item title="Payment" description="Connect a bank" />
            <x-ui::steps.item title="Done" />
        </x-ui::steps>
        <x-ui::button variant="outline" size="sm" wire:click="next">Next step</x-ui::button>
        <x-ui::steps :current="2" orientation="vertical" class="max-w-sm">
            <x-ui::steps.item title="Order placed" description="Jan 3, 10:24" />
            <x-ui::steps.item title="Payment failed" description="Card declined" status="error" />
            <x-ui::steps.item title="Shipped" />
        </x-ui::steps>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Timeline</h2>
        <x-ui::timeline class="max-w-md">
            <x-ui::timeline.item variant="success">
                <x-slot:icon><x-ui::icon name="check" /></x-slot:icon>
                <x-ui::timeline.header><x-ui::timeline.title>Delivered</x-ui::timeline.title><x-ui::timeline.time>Today 14:02</x-ui::timeline.time></x-ui::timeline.header>
                <x-ui::timeline.description>Signed for by M. Foster.</x-ui::timeline.description>
            </x-ui::timeline.item>
            <x-ui::timeline.item variant="info">
                <x-slot:icon><x-ui::icon name="truck" /></x-slot:icon>
                <x-ui::timeline.header><x-ui::timeline.title>Out for delivery</x-ui::timeline.title><x-ui::timeline.time>Today 08:15</x-ui::timeline.time></x-ui::timeline.header>
            </x-ui::timeline.item>
            <x-ui::timeline.item>
                <x-ui::timeline.header><x-ui::timeline.title>Shipped</x-ui::timeline.title><x-ui::timeline.time>Yesterday</x-ui::timeline.time></x-ui::timeline.header>
                <x-ui::timeline.description>Left the Hangzhou warehouse.</x-ui::timeline.description>
            </x-ui::timeline.item>
            <x-ui::timeline.item variant="warning">
                <x-ui::timeline.header><x-ui::timeline.title>Address corrected</x-ui::timeline.title><x-ui::timeline.time>Jan 2</x-ui::timeline.time></x-ui::timeline.header>
            </x-ui::timeline.item>
        </x-ui::timeline>
    </section>
</div>
