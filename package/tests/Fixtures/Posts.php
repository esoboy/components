<?php

namespace Esoboy\Ui\Tests\Fixtures;

use Esoboy\Ui\Livewire\Sanitize;
use Livewire\Component;
use Livewire\Form;

class PostForm extends Form
{
    #[Sanitize]
    public string $body = '';
}

class Posts extends Component
{
    #[Sanitize]
    public string $body = '';

    public string $plain = '';

    public PostForm $form;

    public ?string $seen = null;

    // As an app loading a post would: set on the server, no #[Sanitize] involved.
    public function load(string $html): void
    {
        $this->body = $html;
    }

    public function updatedBody(): void
    {
        $this->seen = $this->body;
    }

    public function render(): string
    {
        return '<div><x-ui::editor wire:model="body" /></div>';
    }
}
