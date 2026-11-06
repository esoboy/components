<?php

use App\Models\User;
use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Livewire\WithDataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    public const COLUMNS = [
        'name' => ['title' => 'Name', 'sortable' => true, 'fixed' => 'start'],
        'email' => ['title' => 'Email', 'sortable' => true],
        'role' => ['title' => 'Role', 'sortable' => true],
        'status' => ['title' => 'Status'],
        'created_at' => ['title' => 'Joined', 'sortable' => true],
    ];

    public const ROLES = ['admin' => ['label' => 'Admin', 'icon' => 'shield'], 'editor' => ['label' => 'Editor', 'icon' => 'pencil'], 'member' => ['label' => 'Member', 'icon' => 'user']];

    public int $step = 2;

    public bool $onlyVerified = false;

    public string $detailNote = 'Review user details';

    /** @var list<string> */
    public array $teams = [];

    /** @return array<string, bool> */
    protected function dataTableSelectionProperties(): array
    {
        return ['selected' => true, 'teams' => false];
    }

    /**
     * The users the search finds, before the filters: what the facets count.
     */
    private function searched(): Builder
    {
        return User::query()
            ->when($this->search, fn ($query) => $query->where('email', 'like', "%{$this->search}%"))
            ->when($this->onlyVerified, fn ($query) => $query->whereNotNull('email_verified_at'));
    }

    #[Computed]
    public function users()
    {
        return $this->sortQuery($this->filterQuery($this->searched(), ['role']), ['name', 'email', 'role', 'created_at'])
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function roleCounts(): array
    {
        return $this->facetCounts($this->searched(), 'role', ['role']);
    }

    /**
     * Budgets by team in $k a quarter, with department totals and nested teams.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function budgets(): array
    {
        $tree = [
            ['Engineering', 'Olivia Martin', [
                ['Platform', 'Liam Chen', [['API', 'Noah Kim', [120, 130, 125, 140]], ['Data', 'Mia Wang', [80, 85, 95, 100]]]],
                ['Product', 'Emma Davis', [90, 95, 110, 105]],
            ]],
            ['Marketing', 'Ava Wilson', [['Brand', 'Lucas Zhang', [40, 45, 50, 55]], ['Growth', 'Sophia Li', [60, 70, 65, 80]]]],
            ['Operations', 'Ethan Brown', [['Support', 'Isabella Wu', [30, 30, 35, 35]]]],
        ];

        $build = function (array $nodes, string $parent = '') use (&$build): array {
            $rows = [];

            foreach ($nodes as [$name, $owner, $budget]) {
                $id = $parent.strtolower($name);
                $children = is_int($budget[0]) ? [] : $build($budget, $id.'/');
                $quarters = $children === []
                    ? $budget
                    : array_map(fn (string $key) => array_sum(array_column($children, $key)), ['q1', 'q2', 'q3', 'q4']);

                $rows[] = [
                    'id' => $id,
                    'name' => $name,
                    'owner' => $owner,
                    'q1' => $quarters[0],
                    'q2' => $quarters[1],
                    'q3' => $quarters[2],
                    'q4' => $quarters[3],
                    'total' => array_sum($quarters),
                    'items' => $children,
                ];
            }

            return $rows;
        };

        return $build($tree);
    }

    public function deleteSelected(): void
    {
        $count = count($this->selected);
        $this->selected = [];
        Ui::toast("{$count} users would be deleted", type: 'warning');
    }

    public function updatedOnlyVerified(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function clearUserFilters(): void
    {
        $this->onlyVerified = false;
        $this->resetFilters();
    }

    public function inspectUser(int $id): void
    {
        Ui::toast(User::query()->findOrFail($id)->email);
    }

    public function updateDetailNote(): void
    {
        $this->detailNote = 'Details reviewed';
    }

    public function markSelected(string $status): void
    {
        Ui::toast(count($this->selected)." users would be marked {$status}");
    }

    public function exportSelected(): void
    {
        Ui::toast(count($this->selected).' users would be exported');
    }

    public function restartSale(): void
    {
        Ui::countdown('flash-sale', 10);
    }

    public function saleEnded(): void
    {
        Ui::toast('The flash sale has ended', type: 'info');
    }

    public function next(): void
    {
        $this->step = min(4, $this->step + 1);
    }
};
?>

<div class="mx-auto w-full max-w-4xl space-y-12 px-4 py-10 sm:px-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Data Table</h2>
        @php
            $users = $this->users;
        @endphp
        <x-ui::data-table selectable>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui::data-table.toolbar collapsible clear-filters clear-action="clearUserFilters">
                    <x-slot:primary>
                        <x-ui::input wire:model.live.debounce.300ms="search" placeholder="Filter emails..." aria-label="Search users" class="h-8 w-[150px] lg:w-[250px]" />
                        <x-ui::data-table.faceted-filter column="role" title="Role" :options="$this::ROLES" :counts="$this->roleCounts" />
                    </x-slot:primary>
                    <x-slot:secondary>
                        <label class="flex items-center gap-2">
                            <x-ui::checkbox wire:model.live="onlyVerified" />
                            Verified users only
                        </label>
                    </x-slot:secondary>
                </x-ui::data-table.toolbar>
                <x-ui::data-table.view-options :columns="$this::COLUMNS" />
            </div>

            <x-ui::data-table.table :rows="$users" :columns="$this::COLUMNS" actions actions-title="Actions" details>
                @foreach ($component->entries() as $entry)
                    @php
                        $user = $entry->row;
                    @endphp
                    <x-ui::data-table.row :entry="$entry">
                        <x-slot:email><span class="lowercase">{{ $user->email }}</span></x-slot:email>
                        <x-slot:role>
                            <span class="flex items-center gap-2">
                                <x-ui::icon :name="$this::ROLES[$user->role]['icon']" class="size-4 text-muted-foreground" />
                                {{ $this::ROLES[$user->role]['label'] }}
                            </span>
                        </x-slot:role>
                        <x-slot:status>
                            <x-ui::badge :variant="$user->email_verified_at ? 'success' : 'warning'">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</x-ui::badge>
                        </x-slot:status>
                        <x-slot:created_at>{{ $user->created_at->format('M j, Y') }}</x-slot:created_at>

                        <x-slot:actions>
                            <x-ui::button variant="ghost" size="sm" wire:click="inspectUser({{ $user->id }})">View user</x-ui::button>
                        </x-slot:actions>

                        <x-slot:detail>
                            <div class="flex flex-wrap items-center justify-between gap-3 p-2">
                                <div>
                                    <p>Email verified {{ $user->email_verified_at?->format('M j, Y H:i') ?? 'Not yet' }}</p>
                                    <p>Last update {{ $user->updated_at->format('M j, Y H:i') }}</p>
                                    <p>{{ $detailNote }}</p>
                                </div>
                                <x-ui::button variant="outline" size="sm" wire:click="updateDetailNote">Update detail note</x-ui::button>
                            </div>
                        </x-slot:detail>
                    </x-ui::data-table.row>
                @endforeach
            </x-ui::data-table.table>

            <div class="flex flex-wrap items-center justify-between gap-3 px-2 py-4">
                <x-ui::data-table.total :total="$users->total()" />
                <x-ui::data-table.page-size :options="$this->perPageOptions()" />
                <x-ui::data-table.pagination :paginator="$users" class="mx-0 w-auto" />
            </div>

            <x-ui::data-table.selection-toolbar class="flex flex-wrap items-center gap-2 rounded-xl bg-popover p-2 text-popover-foreground shadow-lg ring-1 ring-foreground/10">
                <x-ui::button variant="secondary" size="sm" wire:click="markSelected('verified')">Mark verified</x-ui::button>
                <x-ui::button variant="secondary" size="sm" wire:click="exportSelected"><x-ui::icon name="download" data-icon="inline-start" />Export</x-ui::button>
                <x-ui::button variant="destructive" size="sm" wire:click="deleteSelected" wire:confirm="Delete {{ count($selected) }} users?"><x-ui::icon name="trash" data-icon="inline-start" />Delete</x-ui::button>
            </x-ui::data-table.selection-toolbar>
        </x-ui::data-table>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tree rows, fixed columns, totals</h2>
        <p class="text-sm text-muted-foreground">Departments expand into their teams. The header and totals stay while the table scrolls.</p>
        @php
            $treeColumns = [
                'name' => ['title' => 'Department', 'fixed' => 'start'],
                'owner' => ['title' => 'Owner'],
                'q1' => ['title' => 'Q1 ($k)'],
                'q2' => ['title' => 'Q2 ($k)'],
                'q3' => ['title' => 'Q3 ($k)'],
                'q4' => ['title' => 'Q4 ($k)'],
                'total' => ['title' => 'Total ($k)', 'fixed' => 'end'],
            ];
            $budgets = $this->budgets;
            $summary = ['name' => 'Total'];
            foreach (['q1', 'q2', 'q3', 'q4', 'total'] as $quarter) {
                $summary[$quarter] = array_sum(array_column($budgets, $quarter));
            }
        @endphp
        <x-ui::data-table selectable selection="teams">
            <x-ui::data-table.table :rows="$budgets" :columns="$treeColumns" tree children-field="items" details sticky-header class="max-h-80 [&_th:not(:first-child)]:min-w-28">
                @foreach ($component->entries() as $entry)
                    <x-ui::data-table.row :entry="$entry" :expanded="$entry->depth === 0">
                        <x-slot:detail>Budget owner {{ $entry->row['owner'] }}</x-slot:detail>
                    </x-ui::data-table.row>
                @endforeach
                <x-slot:summary>
                    <x-ui::data-table.summary-row :values="$summary" />
                </x-slot:summary>
            </x-ui::data-table.table>

            <x-ui::data-table.selection-toolbar class="static mt-2 flex w-fit items-center gap-2 rounded-xl bg-popover p-2 text-popover-foreground shadow-lg ring-1 ring-foreground/10" />
        </x-ui::data-table>
        <p class="text-sm text-muted-foreground">teams: <span id="teams">{{ implode(', ', $teams) ?: 'none' }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Merged cells</h2>
        <p class="text-sm text-muted-foreground">colspan and rowspan on heads and cells; a merged cell lights up with whichever of its rows is hovered.</p>
        @php
        $sales = ['Hardware' => [['Laptop', 120, 135], ['Monitor', 64, 58], ['Keyboard', 31, 40]], 'Software' => [['License', 210, 240], ['Support plan', 88, 91]]];
        @endphp
        <x-ui::data-table>
            <x-ui::table>
                <x-ui::table.header>
                    <x-ui::table.row>
                        <x-ui::table.head rowspan="2">Category</x-ui::table.head>
                        <x-ui::table.head rowspan="2">Product</x-ui::table.head>
                        <x-ui::table.head colspan="2" class="text-center">Units sold</x-ui::table.head>
                    </x-ui::table.row>
                    <x-ui::table.row>
                        <x-ui::table.head class="text-right">H1</x-ui::table.head>
                        <x-ui::table.head class="text-right">H2</x-ui::table.head>
                    </x-ui::table.row>
                </x-ui::table.header>
                <x-ui::table.body>
                    @foreach ($sales as $category => $products)
                    @foreach ($products as [$product, $first, $second])
                    <x-ui::table.row wire:key="sales-{{ $product }}">
                        @if ($loop->first)
                        <x-ui::table.cell :rowspan="count($products)" class="align-top font-medium">{{ $category }}</x-ui::table.cell>
                        @endif
                        <x-ui::table.cell>{{ $product }}</x-ui::table.cell>
                        <x-ui::table.cell class="text-right tabular-nums">{{ $first }}</x-ui::table.cell>
                        <x-ui::table.cell class="text-right tabular-nums">{{ $second }}</x-ui::table.cell>
                    </x-ui::table.row>
                    @endforeach
                    @endforeach
                </x-ui::table.body>
            </x-ui::table>
        </x-ui::data-table>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Statistic</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui::card>
                <x-ui::card.content>
                    <x-ui::statistic>
                        <x-ui::statistic.label>Total Revenue</x-ui::statistic.label>
                        <x-ui::statistic.value :value="1250" :precision="2" prefix="$" />
                        <x-ui::statistic.trend :value="12.5" />
                    </x-ui::statistic>
                </x-ui::card.content>
                <x-ui::card.footer class="flex-col items-start gap-1.5">
                    <div class="flex gap-2 font-medium">Trending up this month <x-ui::icon name="trending-up" class="size-4" /></div>
                    <div class="text-muted-foreground">Visitors for the last 6 months</div>
                </x-ui::card.footer>
            </x-ui::card>
            <x-ui::card>
                <x-ui::card.content>
                    <x-ui::statistic>
                        <x-ui::statistic.label>New Customers</x-ui::statistic.label>
                        <x-ui::statistic.value :value="1234" />
                        <x-ui::statistic.trend :value="-20" />
                    </x-ui::statistic>
                </x-ui::card.content>
                <x-ui::card.footer class="flex-col items-start gap-1.5">
                    <div class="flex gap-2 font-medium">Down 20% this period <x-ui::icon name="trending-down" class="size-4" /></div>
                    <div class="text-muted-foreground">Acquisition needs attention</div>
                </x-ui::card.footer>
            </x-ui::card>
        </div>
        <div class="flex flex-wrap gap-x-12 gap-y-6">
            <x-ui::statistic>
                <x-ui::statistic.label>Active Accounts</x-ui::statistic.label>
                <x-ui::statistic.value :value="45678" />
                <x-ui::statistic.trend :value="4.5" variant="success" />
            </x-ui::statistic>
            <x-ui::statistic>
                <x-ui::statistic.label>Refund Rate</x-ui::statistic.label>
                <x-ui::statistic.value :value="1.82" suffix="%" />
                <x-ui::statistic.trend :value="0.3" variant="destructive" />
            </x-ui::statistic>
            <x-ui::statistic>
                <x-ui::statistic.label>Storage</x-ui::statistic.label>
                <x-ui::statistic.value>38.6 <span class="text-base font-normal text-muted-foreground">/ 100 GB</span></x-ui::statistic.value>
            </x-ui::statistic>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Countdown</h2>
        <div class="flex flex-wrap items-end gap-x-12 gap-y-6">
            <x-ui::statistic>
                <x-ui::statistic.label>Flash sale ends in</x-ui::statistic.label>
                <x-ui::statistic.value><x-ui::countdown name="flash-sale" :seconds="90" format="mm:ss" wire:finish="saleEnded" /></x-ui::statistic.value>
            </x-ui::statistic>
            <x-ui::statistic>
                <x-ui::statistic.label>Until the new year</x-ui::statistic.label>
                <x-ui::statistic.value><x-ui::countdown :until="now()->addYear()->startOfYear()" format="D [days] HH:mm:ss" /></x-ui::statistic.value>
            </x-ui::statistic>
            <x-ui::button variant="outline" size="sm" wire:click="restartSale">Restart the sale at 10s</x-ui::button>
        </div>
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
