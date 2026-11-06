<?php

namespace Tests\Fixtures;

use Livewire\Component;

class DrawerBinding extends Component
{
    public bool $live = false;

    public bool $drawerOpen = false;

    public int $revision = 1;

    public int $savedRevision = 0;

    public function saveRevision(): void
    {
        $this->savedRevision = $this->revision;
    }

    public function render(): string
    {
        $view = <<<'BLADE'
            <div>
                <x-ui::button id="open-bound-drawer" wire:click="$set('drawerOpen', true)">Open from parent</x-ui::button>
                <x-ui::button id="refresh-drawer-owner" wire:click="$refresh">Refresh parent</x-ui::button>
                <output id="drawer-server-state" data-open="{{ $drawerOpen ? 'true' : 'false' }}"></output>
                <output id="drawer-saved-revision">{{ $savedRevision }}</output>
                <x-ui::drawer name="bound-drawer" wire:model="drawerOpen">
                    <x-ui::drawer.content>
                        <x-ui::drawer.header>
                            <x-ui::drawer.title>Bound drawer</x-ui::drawer.title>
                            <x-ui::drawer.description id="drawer-slot-revision">Revision {{ $revision }}</x-ui::drawer.description>
                        </x-ui::drawer.header>
                        <x-ui::drawer.footer>
                            <x-ui::button id="save-drawer-revision" wire:click="saveRevision" :disabled="$revision === 1">Save revision {{ $revision }}</x-ui::button>
                        </x-ui::drawer.footer>
                    </x-ui::drawer.content>
                </x-ui::drawer>
            </div>
            BLADE;

        return $this->live ? str_replace('wire:model="drawerOpen"', 'wire:model.live="drawerOpen"', $view) : $view;
    }
}
