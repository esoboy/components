<?php

use Livewire\Component;

new class extends Component {
    public ?float $amount = null;
    public array $layout = [];
    public array $selection = [];
    public bool $showInner = true;
    public int $panelVersion = 0;
    public int $buttonVersion = 0;

    public function setAmount(): void
    {
        $this->amount = 2.75;
    }

    public function loadSavedLayout(): void
    {
        $this->layout = ['hidden' => ['title', 'cost', 'region', 'retired']];
    }
};
?>
<main>
    <form id="amounts">
        <x-ui::number-input name="plain" step="0.01" aria-label="Plain amount" />
        <x-ui::number-input name="bound" step="0.01" aria-label="Bound amount" wire:model.live="amount" />
        <output id="saved-amount">{{ $amount }}</output>
        <button type="button" wire:click="setAmount">Set amount</button>
    </form>

    <x-ui::data-table id="orders" layout-model="layout" wire:model.live="selection"
        :rows="[['id' => 'one', 'title' => 'First'], ['id' => 'two', 'title' => 'Second']]"
        :columns="['title' => ['label' => 'Title'], 'cost' => ['label' => 'Cost'], 'region' => ['label' => 'Region']]">
        <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
    </x-ui::data-table>
    <output id="selection">{{ implode(',', $selection) }}</output>
    <output id="hidden-columns">{{ implode(',', $layout['hidden'] ?? []) }}</output>
    <button type="button" wire:click="loadSavedLayout">Load saved layout</button>

    <x-ui::popover>
        <x-ui::popover.trigger><button type="button">Open outer</button></x-ui::popover.trigger>
        <x-ui::popover.content aria-label="Outer">
            <x-ui::popover>
                <x-ui::popover.trigger><button type="button">Open inner</button></x-ui::popover.trigger>
                @if ($showInner)
                    <x-ui::popover.content aria-label="Inner" wire:key="panel-{{ $panelVersion }}">
                        <button type="button" wire:click="$set('showInner', false)">Remove inner</button>
                        <button type="button" wire:click="$set('panelVersion', 1)">Replace inner</button>
                        <span>Panel {{ $panelVersion }}</span>
                    </x-ui::popover.content>
                @endif
            </x-ui::popover>
            <button type="button" wire:click="$set('showInner', true)">Restore inner</button>
        </x-ui::popover.content>
    </x-ui::popover>

    <x-ui::tour id="tour">
        <x-ui::tour.trigger><button type="button">Start tour</button></x-ui::tour.trigger>
        <x-ui::tour.content aria-label="Tour">
            <x-ui::tour.step target="#tour" :index="0">First step</x-ui::tour.step>
            <x-ui::tour.step target="#tour" :index="1">Second step</x-ui::tour.step>
            <button type="button" wire:click="$set('buttonVersion', 1)">Replace tour button</button>
            <x-ui::tour.next><button type="button" wire:key="tour-next-{{ $buttonVersion }}">Tour next {{ $buttonVersion }}</button></x-ui::tour.next>
            <x-ui::tour.close><button type="button">Close tour</button></x-ui::tour.close>
        </x-ui::tour.content>
    </x-ui::tour>

    <button type="button" wire:click="$set('buttonVersion', 2)">Replace carousel button</button>
    <x-ui::carousel id="slides" style="width:320px" :controls="false" pagination="numbers">
        <x-ui::carousel.content>
            <x-ui::carousel.item>One</x-ui::carousel.item><x-ui::carousel.item>Two</x-ui::carousel.item><x-ui::carousel.item>Three</x-ui::carousel.item>
        </x-ui::carousel.content>
        <x-ui::carousel.next><button type="button" wire:key="carousel-next-{{ $buttonVersion }}">Carousel next {{ $buttonVersion }}</button></x-ui::carousel.next>
    </x-ui::carousel>

    <form id="code-form"><x-ui::input-otp name="code" initial-value="1234" :length="4" /></form>
    <x-ui::calendar id="grid-calendar" initial-month="2026-11-01" :disabled-dates="['2026-11-15','2026-11-18','2026-11-24']" />

    <x-ui::drawer :show-swipe-handle="true">
        <x-ui::drawer.trigger><button type="button">Open swipe drawer</button></x-ui::drawer.trigger>
        <x-ui::drawer.content aria-label="Swipe drawer"><x-ui::drawer.body>Swipe to close</x-ui::drawer.body></x-ui::drawer.content>
    </x-ui::drawer>

    <x-ui::button-group id="group-with-select">
        <x-ui::button variant="outline">Before</x-ui::button>
        <x-ui::select id="middle-select" placeholder="Pick" />
        <x-ui::button variant="outline">After</x-ui::button>
    </x-ui::button-group>
    <x-ui::button-group id="group-ending-in-select">
        <x-ui::button variant="outline">Before select</x-ui::button>
        <x-ui::select id="last-select" placeholder="Pick last" />
    </x-ui::button-group>
    <x-ui::toggle-group id="single-toggle" :spacing="0"><x-ui::toggle-group.item>Single</x-ui::toggle-group.item></x-ui::toggle-group>
    <x-ui::toggle-group type="multiple" dir="rtl" style="--item-checked-bg:#224466">
        <x-ui::toggle-group.item>First filter</x-ui::toggle-group.item>
        <x-ui::toggle-group type="multiple"><x-ui::toggle-group.item>Nested filter</x-ui::toggle-group.item></x-ui::toggle-group>
        <x-ui::toggle-group.item disabled>Disabled filter</x-ui::toggle-group.item>
        <x-ui::toggle-group.item>Last filter</x-ui::toggle-group.item>
    </x-ui::toggle-group>
    <x-ui::accordion id="native-accordion">
        <x-ui::accordion.item><x-ui::accordion.summary>Shipping <span data-state="open">info</span></x-ui::accordion.summary><x-ui::accordion.content>Shipping details</x-ui::accordion.content></x-ui::accordion.item>
        <x-ui::accordion.item disabled><x-ui::accordion.summary>Unavailable</x-ui::accordion.summary><x-ui::accordion.content>Unavailable details</x-ui::accordion.content></x-ui::accordion.item>
        <x-ui::accordion.item><x-ui::accordion.summary>Returns</x-ui::accordion.summary><x-ui::accordion.content>Returns details</x-ui::accordion.content></x-ui::accordion.item>
    </x-ui::accordion>
    <x-ui::field orientation="horizontal"><x-ui::field.description id="horizontal-description">Description</x-ui::field.description></x-ui::field>
    <div style="--spacing:8px"><x-ui::progress class="themed-progress" :value="25" /></div>

    <div x-data="{ onViewportResize: false }">
        <x-ui::navigation-menu id="resizing-nav">
            <x-ui::navigation-menu.list><x-ui::navigation-menu.item>
                <x-ui::navigation-menu.trigger value="more"><x-ui::button>Browse sections</x-ui::button></x-ui::navigation-menu.trigger>
            </x-ui::navigation-menu.item></x-ui::navigation-menu.list>
            <x-slot:content><x-ui::navigation-menu.content value="more">
                <x-ui::popover>
                    <x-ui::popover.trigger><x-ui::button>Open section details</x-ui::button></x-ui::popover.trigger>
                    <x-ui::popover.content>Section details</x-ui::popover.content>
                </x-ui::popover>
            </x-ui::navigation-menu.content></x-slot:content>
        </x-ui::navigation-menu>
    </div>
</main>
