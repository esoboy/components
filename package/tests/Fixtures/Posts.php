<?php

namespace Esoboy\Ui\Tests\Fixtures;

use Esoboy\Ui\Facades\Ui;
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

    #[Sanitize('basic')]
    public string $intro = '';

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

    // As an app's own attachment library would insert.
    public function attach(): void
    {
        Ui::editor('body')->insert([['url' => '/a.png', 'name' => 'a.png', 'mime' => 'image/png']]);
    }

    public function template(): void
    {
        Ui::editor('body')->insertContent('<p>Hello</p>');
    }

    public function render(): string
    {
        return '<div><x-ui::editor id="body" wire:model="body" /></div>';
    }
}
