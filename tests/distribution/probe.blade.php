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

    #[On('query-changed')]
    public function filterOptions(string $query): void
    {
        $this->query = $query;
        $this->options = [['value' => 'b', 'label' => 'Beta']];
    }
};
?>
<main>
    <x-ui::icon name="circle-question-mark" />
    <x-ui::button id="rename" wire:click="$set('label', 'Updated')">Rename</x-ui::button>
    <x-ui::button id="custom-button" class="block rounded-lg px-[31px]"><x-ui::icon name="check" data-icon="inline-start" />Save</x-ui::button>
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
        <x-ui::number-input id="quantity" name="quantity" :min="10" :max="99" :initial-value="12" />
        <x-ui::combobox id="single-choice" name="single" wire:model.live="assignee" :options="$options" />
        <x-ui::combobox id="multi-choice" name="multi" wire:model.live="assignees" :options="$options" multiple />
    </form>
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
    <div id="popovers">
        <x-ui::popover>
            <x-ui::popover.trigger><x-ui::button id="outer-trigger">Outer</x-ui::button></x-ui::popover.trigger>
            <x-ui::popover.content>
                <x-ui::popover>
                    <x-ui::popover.trigger><x-ui::button id="inner-trigger">Inner</x-ui::button></x-ui::popover.trigger>
                    <x-ui::popover.content><button type="button">Action</button></x-ui::popover.content>
                </x-ui::popover>
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
