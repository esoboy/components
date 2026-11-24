<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public array $layout = [];
};
?>
<main>
    <x-ui::button id="outside-theme">Outside</x-ui::button>
    <section data-ui-theme="account">
        <span id="theme-utilities" class="rounded-sm text-base">Host utilities</span>
        <x-ui::button id="inside-theme">Inside</x-ui::button>
        <x-ui::badge id="theme-caption">Caption</x-ui::badge>
        <x-ui::card id="theme-card"><x-ui::card.title>Title</x-ui::card.title></x-ui::card>
        <x-ui::popover>
            <x-ui::popover.trigger><x-ui::button id="theme-open">Open</x-ui::button></x-ui::popover.trigger>
            <x-ui::popover.content data-test="theme-popup">Details</x-ui::popover.content>
        </x-ui::popover>
        <x-ui::sidebar id="probe-sidebar" collapsible="icon" side="right">
            <x-ui::sidebar.content>
                <x-ui::sidebar.input id="sidebar-input" />
                <x-ui::sidebar.menu-button tooltip="Draft actions">Drafts</x-ui::sidebar.menu-button>
                <x-ui::sidebar.menu-link href="#archive" tooltip="Archived documents">Archive</x-ui::sidebar.menu-link>
            </x-ui::sidebar.content>
            <x-ui::sidebar.inset><x-ui::sidebar.trigger><x-ui::button id="toggle-sidebar">Toggle</x-ui::button></x-ui::sidebar.trigger></x-ui::sidebar.inset>
        </x-ui::sidebar>
    </section>
    <x-ui::time-picker id="probe-time" name="probe-time" initial-value="09:15" />
    <x-ui::trace.collapsible>
        <x-ui::trace.collapsible-trigger><x-ui::trace.collapsible-button id="toggle-trace">Trace</x-ui::trace.collapsible-button></x-ui::trace.collapsible-trigger>
        <x-ui::trace.collapsible-content id="trace-panel">
            <x-ui::collapsible>
                <x-ui::collapsible.trigger><x-ui::button id="toggle-nested">More</x-ui::button></x-ui::collapsible.trigger>
                <x-ui::collapsible.content data-test="nested-panel">Nested detail</x-ui::collapsible.content>
            </x-ui::collapsible>
        </x-ui::trace.collapsible-content>
    </x-ui::trace.collapsible>
    @php
        $page = $this->getPage('orders');
        $pager = new LengthAwarePaginator([['id' => $page, 'cost' => 5]], 5, 1, $page, ['pageName' => 'orders']);
    @endphp
    <x-ui::data-table id="composition-grid" class="max-w-2xl" :rows="$pager" :columns="['id' => ['label' => 'Order'], 'cost' => ['label' => 'Cost']]" layout-model="layout">
        <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
        <x-slot:footer><x-ui::pagination :paginator="$pager" jumpable /></x-slot:footer>
    </x-ui::data-table>
</main>
