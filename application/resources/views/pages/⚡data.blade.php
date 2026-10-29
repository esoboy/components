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

    /** The users table's columns the user can hide and reorder, as written. */
    public const COLUMNS = ['name' => 'Name', 'email' => 'Email', 'role' => 'Role', 'status' => 'Status', 'created_at' => 'Joined'];

    public const ROLES =['admin' => ['label' => 'Admin', 'icon' => 'shield'], 'editor' => ['label' => 'Editor', 'icon' => 'pencil'], 'member' => ['label' => 'Member', 'icon' => 'user']];

    public int $step = 2;

    /** @var list<string> */
    public array $teams = [];

    /**
     * The users the search finds, before the filters: what the facets count.
     */
    private function searched(): Builder
    {
        return User::query()->when($this->search, fn ($query) => $query->where('email', 'like', "%{$this->search}%"));
    }

    #[Computed]
    public function users()
    {
        return $this->sortQuery($this->filterQuery($this->searched(), ['role']), ['name', 'email', 'role', 'created_at'])->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function roleCounts(): array
    {
        return $this->facetCounts($this->searched(), 'role', ['role']);
    }

    /**
     * Budgets by team in $k a quarter, departments summing their teams, flattened for the table.
     *
     * @return list<array{id: string, parent: ?string, level: int, name: string, owner: string, quarters: list<int>}>
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

        $rows = [];

        $walk = function (array $nodes, ?string $parent, int $level) use (&$walk, &$rows): array {
            $sums = [0, 0, 0, 0];

            foreach ($nodes as [$name, $owner, $budget]) {
                $id = ($parent ? $parent.'/' : '').strtolower($name);
                $index = count($rows);
                $rows[] = ['id' => $id, 'parent' => $parent, 'level' => $level, 'name' => $name, 'owner' => $owner, 'quarters' => []];
                $quarters = is_int($budget[0]) ? $budget : $walk($budget, $id, $level + 1);
                $rows[$index]['quarters'] = $quarters;
                $sums = array_map(fn ($sum, $quarter) => $sum + $quarter, $sums, $quarters);
            }

            return $sums;
        };

        $walk($tree, null, 1);

        return $rows;
    }

    public function deleteSelected(): void
    {
        $count = count($this->selected);
        $this->selected = [];
        Ui::toast("{$count} users would be deleted", type: 'warning');
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

<div class="mx-auto max-w-4xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Data Table</h2>
        <x-ui::data-table style="anchor-name: --users-table">
            <x-ui::data-table.toolbar>
                <x-ui::input wire:model.live.debounce.300ms="search" placeholder="Filter emails..." class="h-8 w-[150px] lg:w-[250px]" />
                <x-ui::data-table.faceted-filter column="role" title="Role" :options="$this::ROLES" :counts="$this->roleCounts" />
                <x-ui::data-table.reset />
                <x-ui::data-table.view-options :columns="$this::COLUMNS" reorderable />
            </x-ui::data-table.toolbar>

            {{-- The columns the user shows, in the user's order; the selection, toggle and actions columns stay put. --}}
            @php
            $visible = $this->visibleColumns($this::COLUMNS);
            $columns = 3 + count($visible);
            @endphp
            <x-ui::data-table.content>
                <x-ui::table.header>
                    <x-ui::table.row>
                        <x-ui::table.head class="w-8"><x-ui::data-table.select-all /></x-ui::table.head>
                        <x-ui::table.head class="w-8 px-0" />
                        @foreach ($visible as $column => $title)
                        @switch ($column)
                            @case('role')
                            <x-ui::table.head><x-ui::data-table.column-header :column="$column" :title="$title" :options="$this::ROLES" /></x-ui::table.head>
                            @break
                            @case('status')
                            <x-ui::table.head><x-ui::data-table.column-header :column="$column" :title="$title" :sortable="false" /></x-ui::table.head>
                            @break
                            @case('created_at')
                            <x-ui::table.head class="text-right"><x-ui::data-table.column-header :column="$column" :title="$title" class="justify-end" /></x-ui::table.head>
                            @break
                            @default
                            <x-ui::table.head><x-ui::data-table.column-header :column="$column" :title="$title" /></x-ui::table.head>
                        @endswitch
                        @endforeach
                        <x-ui::table.head />
                    </x-ui::table.row>
                </x-ui::table.header>
                <x-ui::table.body>
                    @forelse ($this->users as $user)
                    <x-ui::data-table.row :id="$user->id">
                        <x-ui::table.cell><x-ui::data-table.select-row :value="$user->id" /></x-ui::table.cell>
                        <x-ui::table.cell class="px-0"><x-ui::data-table.row-toggle /></x-ui::table.cell>
                        @foreach ($visible as $column => $title)
                        @switch ($column)
                            @case('name')
                            <x-ui::table.cell>{{ $user->name }}</x-ui::table.cell>
                            @break
                            @case('email')
                            <x-ui::table.cell class="lowercase">{{ $user->email }}</x-ui::table.cell>
                            @break
                            @case('role')
                            <x-ui::table.cell><span class="flex items-center gap-2 text-muted-foreground"><x-ui::icon :name="$this::ROLES[$user->role]['icon']" class="size-4" /><span class="text-foreground">{{ $this::ROLES[$user->role]['label'] }}</span></span></x-ui::table.cell>
                            @break
                            @case('status')
                            <x-ui::table.cell><x-ui::badge :variant="$user->email_verified_at ? 'success' : 'warning'">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</x-ui::badge></x-ui::table.cell>
                            @break
                            @case('created_at')
                            <x-ui::table.cell class="text-right">{{ $user->created_at->format('M j, Y') }}</x-ui::table.cell>
                            @break
                        @endswitch
                        @endforeach
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
                    </x-ui::data-table.row>
                    <x-ui::data-table.row-detail :for="$user->id" :colspan="$columns" class="px-4 py-3">
                        <x-ui::description-list :columns="4">
                            <x-ui::description-list.item><x-ui::description-list.term>User ID</x-ui::description-list.term><x-ui::description-list.details>#{{ $user->id }}</x-ui::description-list.details></x-ui::description-list.item>
                            <x-ui::description-list.item><x-ui::description-list.term>Email verified</x-ui::description-list.term><x-ui::description-list.details>{{ $user->email_verified_at?->format('M j, Y H:i') ?? 'Not yet' }}</x-ui::description-list.details></x-ui::description-list.item>
                            <x-ui::description-list.item><x-ui::description-list.term>Joined</x-ui::description-list.term><x-ui::description-list.details>{{ $user->created_at->diffForHumans() }}</x-ui::description-list.details></x-ui::description-list.item>
                            <x-ui::description-list.item><x-ui::description-list.term>Last update</x-ui::description-list.term><x-ui::description-list.details>{{ $user->updated_at->format('M j, Y') }}</x-ui::description-list.details></x-ui::description-list.item>
                        </x-ui::description-list>
                    </x-ui::data-table.row-detail>
                    @empty
                    <x-ui::data-table.empty :colspan="$columns" />
                    @endforelse
                </x-ui::table.body>
            </x-ui::data-table.content>

            <x-ui::data-table.pagination :paginator="$this->users" />

            {{-- Bulk action bar: composed from Button Group, Separator, Dropdown Menu, Tooltip and Button.
                 Fixed to the window's bottom, centred on the table (and hidden with it) through its anchor. --}}
            @if ($selected)
            <div role="toolbar" aria-label="Bulk actions" class="fixed bottom-6 z-40 flex items-center gap-2 rounded-xl bg-popover p-2 text-popover-foreground shadow-lg ring-1 ring-foreground/10 transition duration-200 starting:translate-y-4 starting:opacity-0" style="position-anchor: --users-table; justify-self: anchor-center">
                <x-ui::button-group>
                    <x-ui::button-group.text class="tabular-nums">{{ count($selected) }} selected</x-ui::button-group.text>
                    <x-ui::tooltip>
                        <x-ui::tooltip.trigger variant="outline" size="icon-sm" wire:click="$set('selected', [])" aria-label="Clear selection"><x-ui::icon name="x" /></x-ui::tooltip.trigger>
                        <x-ui::tooltip.content>Clear selection</x-ui::tooltip.content>
                    </x-ui::tooltip>
                </x-ui::button-group>
                <x-ui::separator orientation="vertical" class="my-1" />
                <x-ui::dropdown-menu>
                    <x-ui::dropdown-menu.trigger variant="secondary" size="sm"><x-ui::icon name="circle-check" data-icon="inline-start" />Status</x-ui::dropdown-menu.trigger>
                    <x-ui::dropdown-menu.content side="top" class="w-36">
                        <x-ui::dropdown-menu.item wire:click="markSelected('verified')"><x-ui::icon name="badge-check" />Verified</x-ui::dropdown-menu.item>
                        <x-ui::dropdown-menu.item wire:click="markSelected('pending')"><x-ui::icon name="clock" />Pending</x-ui::dropdown-menu.item>
                    </x-ui::dropdown-menu.content>
                </x-ui::dropdown-menu>
                <x-ui::button variant="secondary" size="sm" wire:click="exportSelected"><x-ui::icon name="download" data-icon="inline-start" />Export</x-ui::button>
                <x-ui::button variant="destructive" size="sm" wire:click="deleteSelected" wire:confirm="Delete {{ count($selected) }} users?"><x-ui::icon name="trash" data-icon="inline-start" />Delete</x-ui::button>
            </div>
            @endif
        </x-ui::data-table>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tree rows, fixed columns, totals</h2>
        <p class="text-sm text-muted-foreground">Departments expand into their teams; the header and the totals stay while the table scrolls, the selection and name columns and the actions stay while it scrolls sideways.</p>
        @php
        $money = fn (int $amount) => '$'.number_format($amount).'k';
        $totals = collect($this->budgets)->where('level', 1)->reduce(fn (array $sums, array $row) => array_map(fn ($sum, $quarter) => $sum + $quarter, $sums, $row['quarters']), [0, 0, 0, 0]);
        @endphp
        <x-ui::data-table>
            <x-ui::data-table.content sticky-header class="max-h-80">
                <x-ui::table.header>
                    <x-ui::table.row>
                        <x-ui::table.head sticky="start" class="w-8"><x-ui::data-table.select-all /></x-ui::table.head>
                        <x-ui::table.head sticky="start" class="min-w-48">Department</x-ui::table.head>
                        <x-ui::table.head class="min-w-36">Owner</x-ui::table.head>
                        @foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter)
                        <x-ui::table.head class="min-w-28 text-right">{{ $quarter }}</x-ui::table.head>
                        @endforeach
                        <x-ui::table.head class="min-w-28 text-right">Total</x-ui::table.head>
                        <x-ui::table.head sticky="end" class="w-12" />
                    </x-ui::table.row>
                </x-ui::table.header>
                <x-ui::table.body>
                    @foreach ($this->budgets as $row)
                    <x-ui::data-table.row :id="$row['id']" :parent="$row['parent']" :level="$row['level']" :expanded="$row['level'] === 1">
                        <x-ui::table.cell sticky="start"><x-ui::data-table.select-row :value="$row['id']" wire:model.live="teams" /></x-ui::table.cell>
                        <x-ui::table.cell sticky="start"><div class="flex items-center gap-1"><x-ui::data-table.row-toggle /><span @class(['font-medium' => $row['level'] === 1])>{{ $row['name'] }}</span></div></x-ui::table.cell>
                        <x-ui::table.cell class="text-muted-foreground">{{ $row['owner'] }}</x-ui::table.cell>
                        @foreach ($row['quarters'] as $amount)
                        <x-ui::table.cell class="text-right tabular-nums">{{ $money($amount) }}</x-ui::table.cell>
                        @endforeach
                        <x-ui::table.cell class="text-right font-medium tabular-nums">{{ $money(array_sum($row['quarters'])) }}</x-ui::table.cell>
                        <x-ui::table.cell sticky="end">
                            <x-ui::dropdown-menu>
                                <x-ui::dropdown-menu.trigger variant="ghost" size="icon" class="size-8"><x-ui::icon name="ellipsis" /><span class="sr-only">Open menu</span></x-ui::dropdown-menu.trigger>
                                <x-ui::dropdown-menu.content align="end" class="w-40">
                                    <x-ui::dropdown-menu.item>Edit budget</x-ui::dropdown-menu.item>
                                    <x-ui::dropdown-menu.item>View report</x-ui::dropdown-menu.item>
                                </x-ui::dropdown-menu.content>
                            </x-ui::dropdown-menu>
                        </x-ui::table.cell>
                    </x-ui::data-table.row>
                    @endforeach
                </x-ui::table.body>
                <x-ui::table.footer>
                    <x-ui::table.row>
                        <x-ui::table.cell sticky="start" />
                        <x-ui::table.cell sticky="start">Total</x-ui::table.cell>
                        <x-ui::table.cell />
                        @foreach ($totals as $amount)
                        <x-ui::table.cell class="text-right tabular-nums">{{ $money($amount) }}</x-ui::table.cell>
                        @endforeach
                        <x-ui::table.cell class="text-right tabular-nums">{{ $money(array_sum($totals)) }}</x-ui::table.cell>
                        <x-ui::table.cell sticky="end" />
                    </x-ui::table.row>
                </x-ui::table.footer>
            </x-ui::data-table.content>
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
            <x-ui::data-table.content>
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
            </x-ui::data-table.content>
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
