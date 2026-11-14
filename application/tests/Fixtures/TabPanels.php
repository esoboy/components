<?php

namespace Tests\Fixtures;

use Livewire\Component;

class TabPanels extends Component
{
    public int $updates = 0;

    public string $tab = 'hour';

    public bool $showTabs = true;

    public function updatePanels(): void
    {
        $this->updates++;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <form id="tab-form">
                <x-ui::button id="update-panels" wire:click="updatePanels">Update</x-ui::button>
                <x-ui::button id="reset-bound-tab" wire:click="$set('tab', 'hour')">Reset bound tab</x-ui::button>
                <x-ui::button id="toggle-tabs" wire:click="$toggle('showTabs')">Toggle tabs</x-ui::button>
                @if ($showTabs)
                <x-ui::tabs id="local-tabs" value="hour" name="local-tab">
                    <x-ui::tabs.list>
                        <x-ui::tabs.trigger value="hour">Hour</x-ui::tabs.trigger>
                        <x-ui::tabs.trigger value="minute">Minute</x-ui::tabs.trigger>
                    </x-ui::tabs.list>
                    <x-ui::tabs.content value="hour">Hour content {{ $updates }}</x-ui::tabs.content>
                    <x-ui::tabs.content value="minute">Minute content {{ $updates }}</x-ui::tabs.content>
                </x-ui::tabs>
                @endif
                <x-ui::tabs id="bound-tabs" wire:model.live="tab">
                    <x-ui::tabs.list>
                        <x-ui::tabs.trigger value="hour">Hour</x-ui::tabs.trigger>
                        <x-ui::tabs.trigger value="minute">Minute</x-ui::tabs.trigger>
                    </x-ui::tabs.list>
                    <x-ui::tabs.content value="hour">Bound hour {{ $updates }}</x-ui::tabs.content>
                    <x-ui::tabs.content value="minute">Bound minute {{ $updates }}</x-ui::tabs.content>
                </x-ui::tabs>
                <output id="updates">{{ $updates }}</output>
                <output id="active-tab">{{ $tab }}</output>
            </form>
            BLADE;
    }
}
