<?php

namespace Tests\Fixtures;

use Livewire\Component;

class ControlSlots extends Component
{
    public string $choice = 'a';

    public string $label = 'Before';

    public string $query = '';

    public string $message = '';

    public bool $disabled = false;

    public bool $showCounter = true;

    public function record(string $source): void
    {
        $this->message = $source;
        $this->label = 'After';
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <button id="update-label" wire:click="$set('label', 'Updated')">Update label</button>
                <button id="toggle-disabled" wire:click="$toggle('disabled')">Toggle disabled</button>
                <button id="toggle-counter" wire:click="$toggle('showCounter')">Toggle child</button>
                <output id="message">{{ $message }}</output>
                <output id="choice">{{ $choice }}</output>
                <output id="query">{{ $query }}</output>
                <x-ui::select id="custom-select" wire:model.live="choice">
                    <x-ui::select.trigger><x-ui::select.value /></x-ui::select.trigger>
                    <x-ui::select.content>
                        <x-ui::select.item value="a">{{ $label }}</x-ui::select.item>
                        <x-ui::select.item value="b">Beta</x-ui::select.item>
                        <button id="select-action" wire:click="record('select')">{{ $label }} action</button>
                        <form wire:submit="record('form')"><button id="select-submit" type="submit">Submit</button></form>
                    </x-ui::select.content>
                </x-ui::select>
                <x-ui::command id="custom-command">
                    <x-ui::command.input />
                    <x-ui::command.list>
                        <x-ui::command.empty><button id="empty-action" wire:click="record('empty')">{{ $label }} empty</button></x-ui::command.empty>
                        <x-ui::command.item value="alpha" wire:click="record('item')">{{ $label }} Alpha</x-ui::command.item>
                    </x-ui::command.list>
                    <button id="command-action" wire:click="record('command')">{{ $label }} footer</button>
                    @if ($showCounter)<livewire:slot-counter wire:key="command-counter" />@endif
                </x-ui::command>
                <x-ui::command id="bound-command">
                    <x-ui::command.input wire:model.live="query" />
                    <x-ui::command.item value="beta" wire:click="record('bound')">Beta</x-ui::command.item>
                </x-ui::command>
                <form id="native-values">
                    <x-ui::combobox name="combo" value="a" :options="['a' => 'A']" :disabled="$disabled" />
                    <x-ui::tags-input name="tags" :value="['a']" :disabled="$disabled" />
                    <x-ui::rating name="rating" :value="3" :disabled="$disabled" />
                    <x-ui::tree name="tree" value="a" :items="[['value' => 'a', 'label' => 'A']]" :disabled="$disabled" />
                </form>
            </div>
            BLADE;
    }
}
