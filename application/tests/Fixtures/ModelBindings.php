<?php

namespace Tests\Fixtures;

use Livewire\Component;

class ModelBindings extends Component
{
    public string $body = '<p>Before</p>';

    public int $quantity = 1;

    public int $rating = 2;

    public int $updates = 0;

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
            </div>
            BLADE;
    }
}
