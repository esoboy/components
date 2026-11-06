<?php

namespace Esoboy\Ui\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class InputCount extends Component
{
    #[Reactive]
    public string $initial = '';

    #[Reactive]
    public ?int $max = null;

    public ?string $draft = null;

    public ?string $draftSource = null;

    public function countText(string $text): void
    {
        $this->draft = $text;
        $this->draftSource = $this->initial;
    }

    public function render(): View
    {
        $text = $this->draftSource === $this->initial ? ($this->draft ?? $this->initial) : $this->initial;

        return view('ui::livewire.input-count', ['count' => mb_strlen($text)]);
    }
}
