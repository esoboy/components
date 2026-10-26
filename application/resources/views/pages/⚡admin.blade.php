<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'invoices';

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $methods = ['Credit Card', 'PayPal', 'Bank Transfer'];
        $statuses = ['Paid', 'Pending', 'Unpaid'];
        $all = collect(range(1, 87))->map(fn ($n) => [
            'invoice' => sprintf('INV%03d', $n),
            'status' => $statuses[$n % 3],
            'method' => $methods[$n % 3],
            'amount' => sprintf('$%d.00', 100 + ($n * 37) % 400),
        ]);

        return (new LengthAwarePaginator($all->forPage($this->getPage(), 5)->values(), $all->count(), 5, $this->getPage(), ['path' => request()->url()]))->onEachSide(1);
    }
};
?>

@php
$icon = fn (string $paths) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$paths.'</svg>';
$home = $icon('<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>');
$inbox = $icon('<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>');
$calendar = $icon('<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>');
$settings = $icon('<path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/><circle cx="12" cy="12" r="3"/>');
$plus = $icon('<path d="M5 12h14"/><path d="M12 5v14"/>');
$more = $icon('<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>');
$command = $icon('<path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/>');
$folder = $icon('<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>');
$chevronsUpDown = $icon('<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>');
@endphp

<x-ui::sidebar.provider>
    <x-ui::sidebar collapsible="icon">
        <x-ui::sidebar.header>
            <x-ui::sidebar.menu>
                <x-ui::sidebar.menu-item>
                    <x-ui::sidebar.menu-button size="lg" href="#">
                        <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">{!! $command !!}</div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-medium">Acme Inc</span>
                            <span class="truncate text-xs">Enterprise</span>
                        </div>
                    </x-ui::sidebar.menu-button>
                </x-ui::sidebar.menu-item>
            </x-ui::sidebar.menu>
            <x-ui::sidebar.input placeholder="Search the docs..." class="in-data-[collapsible=icon]:hidden" />
        </x-ui::sidebar.header>
        <x-ui::sidebar.content>
            <x-ui::sidebar.group>
                <x-ui::sidebar.group-label>Application</x-ui::sidebar.group-label>
                <x-ui::sidebar.group-action title="Add Project">{!! $plus !!}<span class="sr-only">Add Project</span></x-ui::sidebar.group-action>
                <x-ui::sidebar.group-content>
                    <x-ui::sidebar.menu>
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button href="#" is-active tooltip="Home">{!! $home !!}<span>Home</span></x-ui::sidebar.menu-button>
                        </x-ui::sidebar.menu-item>
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button href="#" tooltip="Inbox">{!! $inbox !!}<span>Inbox</span></x-ui::sidebar.menu-button>
                            <x-ui::sidebar.menu-badge>24</x-ui::sidebar.menu-badge>
                        </x-ui::sidebar.menu-item>
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button href="#" tooltip="Calendar">{!! $calendar !!}<span>Calendar</span></x-ui::sidebar.menu-button>
                            <x-ui::sidebar.menu-action show-on-hover>{!! $more !!}<span class="sr-only">More</span></x-ui::sidebar.menu-action>
                        </x-ui::sidebar.menu-item>
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button href="#" tooltip="Settings">{!! $settings !!}<span>Settings</span></x-ui::sidebar.menu-button>
                            <x-ui::sidebar.menu-sub>
                                <x-ui::sidebar.menu-sub-item>
                                    <x-ui::sidebar.menu-sub-button href="#" is-active><span>General</span></x-ui::sidebar.menu-sub-button>
                                </x-ui::sidebar.menu-sub-item>
                                <x-ui::sidebar.menu-sub-item>
                                    <x-ui::sidebar.menu-sub-button href="#"><span>Team</span></x-ui::sidebar.menu-sub-button>
                                </x-ui::sidebar.menu-sub-item>
                                <x-ui::sidebar.menu-sub-item>
                                    <x-ui::sidebar.menu-sub-button href="#"><span>Billing</span></x-ui::sidebar.menu-sub-button>
                                </x-ui::sidebar.menu-sub-item>
                            </x-ui::sidebar.menu-sub>
                        </x-ui::sidebar.menu-item>
                    </x-ui::sidebar.menu>
                </x-ui::sidebar.group-content>
            </x-ui::sidebar.group>
            <x-ui::sidebar.separator />
            <x-ui::sidebar.group>
                <x-ui::sidebar.group-label>Projects</x-ui::sidebar.group-label>
                <x-ui::sidebar.group-content>
                    <x-ui::sidebar.menu>
                        @foreach (['Design Engineering', 'Sales & Marketing', 'Travel'] as $project)
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button href="#" :tooltip="$project">{!! $folder !!}<span>{{ $project }}</span></x-ui::sidebar.menu-button>
                        </x-ui::sidebar.menu-item>
                        @endforeach
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-skeleton show-icon />
                        </x-ui::sidebar.menu-item>
                    </x-ui::sidebar.menu>
                </x-ui::sidebar.group-content>
            </x-ui::sidebar.group>
        </x-ui::sidebar.content>
        <x-ui::sidebar.footer>
            <x-ui::sidebar.menu>
                <x-ui::sidebar.menu-item>
                    <x-ui::sidebar.menu-button size="lg">
                        <x-ui::avatar class="size-8 rounded-lg"><x-ui::avatar.fallback class="rounded-lg">CN</x-ui::avatar.fallback></x-ui::avatar>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-medium">shadcn</span>
                            <span class="truncate text-xs">m@example.com</span>
                        </div>
                        {!! $chevronsUpDown !!}
                    </x-ui::sidebar.menu-button>
                </x-ui::sidebar.menu-item>
            </x-ui::sidebar.menu>
        </x-ui::sidebar.footer>
        <x-ui::sidebar.rail />
    </x-ui::sidebar>

    <x-ui::sidebar.inset>
        <header class="flex h-16 shrink-0 items-center gap-2 border-b px-4">
            <x-ui::sidebar.trigger class="-ml-1" />
            <x-ui::separator orientation="vertical" class="mr-2 h-4 self-center" />
            <x-ui::breadcrumb>
                <x-ui::breadcrumb.list>
                    <x-ui::breadcrumb.item class="hidden md:block">
                        <x-ui::breadcrumb.link href="#">Building Your Application</x-ui::breadcrumb.link>
                    </x-ui::breadcrumb.item>
                    <x-ui::breadcrumb.separator class="hidden md:block" />
                    <x-ui::breadcrumb.item>
                        <x-ui::breadcrumb.ellipsis />
                    </x-ui::breadcrumb.item>
                    <x-ui::breadcrumb.separator />
                    <x-ui::breadcrumb.item>
                        <x-ui::breadcrumb.page>Data Fetching</x-ui::breadcrumb.page>
                    </x-ui::breadcrumb.item>
                </x-ui::breadcrumb.list>
            </x-ui::breadcrumb>
        </header>

        <div class="flex flex-1 flex-col gap-10 p-6">
            <x-ui::tabs wire:model.live="tab" class="max-w-2xl">
                <x-ui::tabs.list>
                    <x-ui::tabs.trigger value="invoices">Invoices</x-ui::tabs.trigger>
                    <x-ui::tabs.trigger value="account">Account</x-ui::tabs.trigger>
                    <x-ui::tabs.trigger value="disabled" disabled>Disabled</x-ui::tabs.trigger>
                </x-ui::tabs.list>
                <x-ui::tabs.content value="invoices">
                    <x-ui::table>
                        <x-ui::table.caption>A list of your recent invoices. Tab: {{ $tab }}</x-ui::table.caption>
                        <x-ui::table.header>
                            <x-ui::table.row>
                                <x-ui::table.head class="w-[100px]">Invoice</x-ui::table.head>
                                <x-ui::table.head>Status</x-ui::table.head>
                                <x-ui::table.head>Method</x-ui::table.head>
                                <x-ui::table.head class="text-right">Amount</x-ui::table.head>
                            </x-ui::table.row>
                        </x-ui::table.header>
                        <x-ui::table.body>
                            @foreach ($this->invoices as $row)
                            <x-ui::table.row wire:key="{{ $row['invoice'] }}">
                                <x-ui::table.cell class="font-medium">{{ $row['invoice'] }}</x-ui::table.cell>
                                <x-ui::table.cell>{{ $row['status'] }}</x-ui::table.cell>
                                <x-ui::table.cell>{{ $row['method'] }}</x-ui::table.cell>
                                <x-ui::table.cell class="text-right">{{ $row['amount'] }}</x-ui::table.cell>
                            </x-ui::table.row>
                            @endforeach
                        </x-ui::table.body>
                        <x-ui::table.footer>
                            <x-ui::table.row>
                                <x-ui::table.cell colspan="3">Total</x-ui::table.cell>
                                <x-ui::table.cell class="text-right">$2,500.00</x-ui::table.cell>
                            </x-ui::table.row>
                        </x-ui::table.footer>
                    </x-ui::table>
                    <x-ui::pagination :paginator="$this->invoices" class="mt-4" />
                </x-ui::tabs.content>
                <x-ui::tabs.content value="account">
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>Account</x-ui::card.title>
                            <x-ui::card.description>Make changes to your account here.</x-ui::card.description>
                        </x-ui::card.header>
                    </x-ui::card>
                </x-ui::tabs.content>
            </x-ui::tabs>

            <x-ui::tabs default-value="overview">
                <x-ui::tabs.list variant="line">
                    <x-ui::tabs.trigger value="overview">Overview</x-ui::tabs.trigger>
                    <x-ui::tabs.trigger value="analytics">Analytics</x-ui::tabs.trigger>
                    <x-ui::tabs.trigger value="reports">Reports</x-ui::tabs.trigger>
                </x-ui::tabs.list>
                <x-ui::tabs.content value="overview">Overview content.</x-ui::tabs.content>
                <x-ui::tabs.content value="analytics">Analytics content.</x-ui::tabs.content>
                <x-ui::tabs.content value="reports">Reports content.</x-ui::tabs.content>
            </x-ui::tabs>

            <x-ui::tabs default-value="general" orientation="vertical">
                <x-ui::tabs.list>
                    <x-ui::tabs.trigger value="general">General</x-ui::tabs.trigger>
                    <x-ui::tabs.trigger value="security">Security</x-ui::tabs.trigger>
                </x-ui::tabs.list>
                <x-ui::tabs.content value="general">General settings.</x-ui::tabs.content>
                <x-ui::tabs.content value="security">Security settings.</x-ui::tabs.content>
            </x-ui::tabs>

            <x-ui::pagination>
                <x-ui::pagination.content>
                    <x-ui::pagination.item><x-ui::pagination.previous href="#" /></x-ui::pagination.item>
                    <x-ui::pagination.item><x-ui::pagination.link href="#">1</x-ui::pagination.link></x-ui::pagination.item>
                    <x-ui::pagination.item><x-ui::pagination.link href="#" is-active>2</x-ui::pagination.link></x-ui::pagination.item>
                    <x-ui::pagination.item><x-ui::pagination.link href="#">3</x-ui::pagination.link></x-ui::pagination.item>
                    <x-ui::pagination.item><x-ui::pagination.ellipsis /></x-ui::pagination.item>
                    <x-ui::pagination.item><x-ui::pagination.next href="#" /></x-ui::pagination.item>
                </x-ui::pagination.content>
            </x-ui::pagination>
        </div>
    </x-ui::sidebar.inset>
</x-ui::sidebar.provider>
