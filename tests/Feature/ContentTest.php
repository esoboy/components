<?php

use Esoboy\Ui\Livewire\Mentions;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('suggests and inserts mention tokens without fetching users', function () {
    Livewire::test(Mentions::class, ['initialValue' => 'Hi @al', 'options' => [['value' => 'alice', 'label' => 'Alice']]])
        ->assertSee('Alice')->call('choose', 'alice', 'al')->assertSet('value', 'Hi @alice ')->assertDontSee('role="listbox"', false);
});

it('rejects a mention selection from an obsolete query without changing the newer text', function () {
    Livewire::test(Mentions::class, ['initialValue' => 'Hi @al', 'options' => [['value' => 'alice', 'label' => 'Alice'], ['value' => 'bob', 'label' => 'Bob']]])
        ->set('value', 'New @bo')->call('choose', 'alice', 'al')
        ->assertSet('value', 'New @bo')->assertNotDispatched('value-changed');
});

it('renders every content entry through its public facade', function (string $template) {
    expect(Blade::render($template))->not->toBe('');
})->with([
    '<x-ui::editor id="editor" />',
    '<x-ui::mentions id="mentions" />',
    '<x-ui::signature id="signature" />',
    '<x-ui::cropper id="cropper" src="/example.jpg" />',
    '<x-ui::qr-code value="https://example.com" />',
    '<x-ui::code-block code="echo 1;" />',
]);
