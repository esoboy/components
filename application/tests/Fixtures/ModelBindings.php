<?php

namespace Tests\Fixtures;

use Livewire\Component;

class ModelBindings extends Component
{
    public string $body = '<p>Before</p>';

    public int $quantity = 1;

    public int $rating = 2;

    public int $updates = 0;

    public int $blurQuantity = 1;

    public string $command = '';

    public string $commandAction = '';

    public bool $showHeader = false;

    public string $counted = 'a';

    public function chooseCommand(string $command): void
    {
        $this->commandAction = $command;
    }

    public function updated(): void
    {
        $this->updates++;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-ui::editor id="body-editor" wire:model.live.blur="body" format="basic" />
                <x-ui::number-input id="quantity" wire:model.live.debounce.250ms="quantity" />
                <x-ui::rating id="rating" wire:model.lazy="rating" />
                <button id="outside" type="button">Outside</button>
                <output id="server-body">{{ $body }}</output>
                <output id="server-quantity">{{ $quantity }}</output>
                <output id="server-rating">{{ $rating }}</output>
                <output id="server-updates">{{ $updates }}</output>
                <x-ui::number-input id="blur-quantity" wire:model.live.blur="blurQuantity" />
                <output id="server-blur-quantity">{{ $blurQuantity }}</output>
                <div id="duplicate-quantity">
                    <x-ui::number-input wire:model="quantity" />
                    <x-ui::number-input wire:model="quantity" />
                </div>
                <x-ui::command id="bound-command">
                    <x-ui::command.input wire:model.live="command" />
                    <x-ui::command.item value="archive" wire:click.prevent="chooseCommand('archive')">Archive</x-ui::command.item>
                    <x-ui::command.item value="copy"><button type="button" wire:click.stop="chooseCommand('copy')">Copy</button></x-ui::command.item>
                </x-ui::command>
                <output id="server-command">{{ $command }}</output>
                <output id="server-command-action">{{ $commandAction }}</output>
                <x-ui::input-group>
                    <x-ui::input-group.input id="counted" wire:model="counted" />
                    <x-ui::input-group.count :max="20" id="draft-count" />
                </x-ui::input-group>
                <button id="replace-draft" wire:click="$set('counted', 'ccc')">Replace draft</button>
                <button id="reset-draft" wire:click="$set('counted', 'a')">Reset draft</button>
                <button id="toggle-header" wire:click="$toggle('showHeader')">Toggle header</button>
                <x-ui::calendar>
                    <x-slot:header>
                        @if ($showHeader)
                            <span id="calendar-header">Custom calendar header</span>
                        @endif
                    </x-slot:header>
                </x-ui::calendar>
            </div>
            BLADE;
    }
}
