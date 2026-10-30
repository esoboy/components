<?php

namespace Esoboy\Ui\Tests\Fixtures;

use Esoboy\Ui\Facades\Ui;
use Livewire\Component;

class Posts extends Component
{
    public string $body = '';

    public ?string $saved = null;

    // As an app loading a post would: set on the server.
    public function load(string $html): void
    {
        $this->body = $html;
    }

    // As an app stores what the Editor sends back: sanitized to its format first.
    public function save(): void
    {
        $this->saved = Ui::sanitize($this->body);
    }

    public function render(): string
    {
        return '<div><x-ui::editor wire:model="body" /></div>';
    }
}
