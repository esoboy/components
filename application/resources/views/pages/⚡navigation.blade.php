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

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Sidebar</h2>
        <p class="text-sm text-muted-foreground">The navigation of this demo is the Sidebar: collapse it with the trigger, the rail or Ctrl/⌘ B; the state survives reloads.</p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Breadcrumb</h2>
        <x-ui::breadcrumb>
            <x-ui::breadcrumb.list>
                <x-ui::breadcrumb.item>
                    <x-ui::breadcrumb.link href="#">Building Your Application</x-ui::breadcrumb.link>
                </x-ui::breadcrumb.item>
                <x-ui::breadcrumb.separator />
                <x-ui::breadcrumb.item>
                    <x-ui::breadcrumb.ellipsis />
                </x-ui::breadcrumb.item>
                <x-ui::breadcrumb.separator />
                <x-ui::breadcrumb.item>
                    <x-ui::breadcrumb.page>Data Fetching</x-ui::breadcrumb.page>
                </x-ui::breadcrumb.item>
            </x-ui::breadcrumb.list>
        </x-ui::breadcrumb>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tabs</h2>
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
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Pagination</h2>
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
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Navigation Menu</h2>
        <x-ui::navigation-menu>
            <x-ui::navigation-menu.list>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.trigger>Getting started</x-ui::navigation-menu.trigger>
                    <x-ui::navigation-menu.content>
                        <ul class="grid w-96 gap-1 p-1">
                            @foreach (['Introduction' => 'Re-usable components built with Tailwind CSS.', 'Installation' => 'How to install dependencies and structure your app.', 'Typography' => 'Styles for headings, paragraphs, lists...etc'] as $title => $text)
                            <li>
                                <x-ui::navigation-menu.link href="#" class="flex-col items-start gap-1">
                                    <div class="text-sm leading-none font-medium">{{ $title }}</div>
                                    <p class="line-clamp-2 text-sm leading-snug text-muted-foreground">{{ $text }}</p>
                                </x-ui::navigation-menu.link>
                            </li>
                            @endforeach
                        </ul>
                    </x-ui::navigation-menu.content>
                </x-ui::navigation-menu.item>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.trigger>Components</x-ui::navigation-menu.trigger>
                    <x-ui::navigation-menu.content>
                        <ul class="grid w-[500px] grid-cols-2 gap-1 p-1">
                            @foreach (['Alert Dialog', 'Hover Card', 'Progress', 'Scroll-area', 'Tabs', 'Tooltip'] as $title)
                            <li><x-ui::navigation-menu.link href="#">{{ $title }}</x-ui::navigation-menu.link></li>
                            @endforeach
                        </ul>
                    </x-ui::navigation-menu.content>
                </x-ui::navigation-menu.item>
                <x-ui::navigation-menu.item>
                    <x-ui::navigation-menu.link href="#">Docs</x-ui::navigation-menu.link>
                </x-ui::navigation-menu.item>
            </x-ui::navigation-menu.list>
        </x-ui::navigation-menu>
    </section>
</div>
