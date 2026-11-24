<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public string $label = 'Choose';
    public int $calls = 0;
    public bool $editing = false;
    public array $options = [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']];
    public string $query = '';
    public string $assignee = 'a';
    public array $assignees = ['a'];
    public string $controlledBody = '<p><strong>First</strong></p>';
    public ?string $minimum = null;
    public ?string $questionItem = null;
    public string $requestedItem = '';
    public bool $acceptItem = false;

    #[On('query-changed')]
    public function filterOptions(string $query): void
    {
        $this->query = $query;
        $this->options = [['value' => 'b', 'label' => 'Beta']];
    }

    #[On('item-changed')]
    public function requestItem(string $item): void
    {
        $this->requestedItem = $item;
        if ($this->acceptItem) {
            $this->questionItem = $item;
        }
    }
};
?>
<main>
    @if (request()->boolean('deferredEditor'))
        <form id="deferred-draft" x-data="{ mounted: false }">
            <button type="button" x-on:click="$wire.$set('controlledBody', 'Updated draft', false); mounted = true">Mount draft</button>
            <template x-if="mounted"><div><x-ui::editor id="deferred-editor" wire:model="controlledBody" output-type="markdown" /></div></template>
        </form>
    @endif
    <x-ui::icon name="circle-question-mark" />
    <x-ui::button id="rename" wire:click="$set('label', 'Updated')">Rename</x-ui::button>
    <x-ui::button id="custom-button" class="block rounded-md px-[31px]"><x-ui::icon name="check" data-icon="inline-start" />Save</x-ui::button>
    <x-ui::button id="disabled-button" disabled>Save</x-ui::button>
    <x-ui::button id="disabled-link" link="/orders" disabled>Orders</x-ui::button>
    <x-ui::popover.title id="custom-title" class="font-normal">Details</x-ui::popover.title>
    <x-ui::rich-text id="custom-prose" class="text-[22px]" value="Preview" />
    <x-ui::spinner id="large-spinner" size="lg" />
    <x-ui::resizable>
        <x-ui::resizable.panel id="fixed-panel" :size="25" class="flex-none">Fixed</x-ui::resizable.panel>
    </x-ui::resizable>
    <x-ui::select id="choice" :options="[['value' => 'one', 'label' => 'One']]">
        <x-slot:footer>
            <x-ui::button id="caller-action" wire:click="$set('calls', {{ $calls + 1 }})">{{ $label }}</x-ui::button>
        </x-slot:footer>
    </x-ui::select>
    <output id="calls">{{ $calls }}</output>
    <form id="controls" x-on:submit.prevent>
        <label for="quantity">Quantity</label>
        <x-ui::number-input id="quantity" name="quantity" required placeholder="Units" :min="10" :max="99" :initial-value="12" />
        <x-ui::combobox id="single-choice" name="single" wire:model.live="assignee" :options="$options" />
        <x-ui::combobox id="multi-choice" name="multi" wire:model.live="assignees" :options="$options" multiple />
    </form>
    <form id="verification"></form>
    <x-ui::input-otp name="code" form="verification" :length="2" initial-value="12" required />
    <output id="filtered">{{ $query }}</output>
    <output id="chosen">{{ implode(',', $assignees) }}</output>
    <x-ui::button id="new-assignee" wire:click="$set('assignee', 'b')">Assign Beta</x-ui::button>
    <button id="start-calendar" wire:click="$set('minimum', '2027-04-01')">Show booking dates</button>
    @if ($minimum !== null)
        <form id="booking-dates">
            <x-ui::calendar id="booking" name="period" range initial-month="2027-06-01" :min="$minimum" caption-layout="dropdown" />
        </form>
        <button id="limit-july" wire:click="$set('minimum', '2027-07-10')">From July 10</button>
        <button id="limit-september" wire:click="$set('minimum', '2027-09-10')">From September 10</button>
    @endif
    <button id="start-questions" wire:click="$set('questionItem', 'second')">Show questions</button>
    @if ($questionItem !== null)
        <div id="controlled-questions">
            <x-ui::questionnaire id="survey" :item="$questionItem" initial-item="first"
                :items="[['name' => 'first', 'prompt' => 'First question'], ['name' => 'second', 'prompt' => 'Second question']]" />
            <output id="requested-item">{{ $requestedItem }}</output>
            <button id="accept-item" wire:click="$set('acceptItem', true)" aria-pressed="{{ $acceptItem ? 'true' : 'false' }}">Accept navigation</button>
        </div>
    @endif
    <x-ui::dropdown-menu>
        <x-ui::dropdown-menu.trigger><x-ui::button id="menu-trigger">Actions</x-ui::button></x-ui::dropdown-menu.trigger>
        <x-ui::dropdown-menu.content>
            <x-ui::dropdown-menu.item>Archive</x-ui::dropdown-menu.item>
            <x-ui::dropdown-menu.item disabled>Delete</x-ui::dropdown-menu.item>
            <x-ui::dropdown-menu.item>Rename</x-ui::dropdown-menu.item>
        </x-ui::dropdown-menu.content>
    </x-ui::dropdown-menu>
    <div id="popovers">
        <x-ui::popover>
            <x-ui::popover.trigger><x-ui::button id="outer-trigger">Outer</x-ui::button></x-ui::popover.trigger>
            <x-ui::popover.content>
                <x-ui::popover>
                    <x-ui::popover.trigger><x-ui::button id="inner-trigger">Inner</x-ui::button></x-ui::popover.trigger>
                    <x-ui::popover.content><button type="button">Action</button></x-ui::popover.content>
                </x-ui::popover>
                <x-ui::button id="second-action">Second action</x-ui::button>
            </x-ui::popover.content>
        </x-ui::popover>
    </div>
    <x-ui::button id="start-editing" wire:click="$set('editing', true)">Edit</x-ui::button>
    @if ($editing)
        <form id="documents">
            <x-ui::editor id="first-editor" name="body" initial-value="**First**" input-type="markdown" output-type="html" />
            <x-ui::editor id="second-editor" name="controlled" :value="$controlledBody" input-type="html" output-type="markdown" />
            <button id="reset-documents" type="reset">Reset</button>
        </form>
        <button id="replace-body" type="button" wire:click="$set('controlledBody', '<p>Second</p>')">Replace</button>
        <button id="stop-editing" type="button" wire:click="$set('editing', false)">Close editors</button>
    @endif
</main>
