<?php

namespace Tests\Fixtures;

use Livewire\Component;

class SlotCounter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): string
    {
        return '<button id="slot-counter" wire:click="increment">Child {{ $count }}</button>';
    }
}
