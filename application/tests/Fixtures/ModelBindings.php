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

    public string $slotBody = '<p>Slot editor</p>';

    public int $toolbarActions = 0;

    public bool $toolbarEnabled = false;

    public string $fixtureTime = '10:30';

    public string $confirmedTime = '';

    public function useToolbarAction(): void
    {
        $this->toolbarActions++;
    }

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
                <span id="body-editor-label">Article body</span>
                <x-ui::editor id="body-editor"
                    :aria-label="str_contains($body, '!') ? 'Review & publish article' : 'Edit & review article'"
                    :aria-labelledby="str_contains($body, '!') ? null : 'body-editor-label'"
                    wire:model.live.blur="body" format="basic" />
                <button id="enable-toolbar-action" type="button" wire:click="$toggle('toolbarEnabled')">Toggle toolbar action</button>
                <output id="toolbar-actions">{{ $toolbarActions }}</output>
                <x-ui::editor id="slot-editor" wire:model="slotBody" :format="['basic', 'character-count']" style="width: 235px">
                    <x-ui::editor.toolbar>
                        <x-ui::button wire:key="toolbar-first" data-priority="1">Main controls</x-ui::button>
                        <x-ui::editor.separator />
                        <x-ui::button wire:key="toolbar-action" id="toolbar-action" data-priority="3" wire:click="useToolbarAction" :disabled="! $toolbarEnabled">{{ $toolbarEnabled ? 'Run toolbar action' : 'Toolbar action disabled' }}</x-ui::button>
                        @if ($toolbarActions)
                            <x-ui::button wire:key="toolbar-result" id="toolbar-result" data-priority="3">Action ran {{ $toolbarActions }}</x-ui::button>
                        @endif
                    </x-ui::editor.toolbar>
                </x-ui::editor>
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
                <x-ui::time-picker id="fixture-time" mode="combined" wire:model.live="fixtureTime">
                    <x-ui::button id="time-slot-action" wire:click="$set('confirmedTime', '{{ $fixtureTime }}')">Confirm {{ $fixtureTime }}</x-ui::button>
                </x-ui::time-picker>
                <output id="time-slot-result">{{ $confirmedTime }}</output>
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
