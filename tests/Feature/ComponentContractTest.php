<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Livewire\TagsInput;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('passes application and native attributes to the editor element itself', function () {
    $value = '<p>A &amp; B</p>';
    $document = HTMLDocument::createFromString(Blade::render(<<<'BLADE'
        <x-ui::editor id="draft" class="compact" name="body" form="post" wire:model.blur="draft"
            data-application="tenant" aria-describedby="help" x-ref="editor" readonly />
        <x-ui::editor id="raw" :value="$value" />
        BLADE, compact('value')), LIBXML_NOERROR);
    $editor = $document->getElementById('draft');
    expect($editor->localName)->toBe('esoboy-editor')
        ->and($editor->classList->contains('compact'))->toBeTrue()
        ->and($editor->hasAttribute('readonly'))->toBeTrue()
        ->and($document->getElementById('raw')->getAttribute('value'))->toBe($value);
    foreach (['name' => 'body', 'form' => 'post', 'wire:model.blur' => 'draft', 'data-application' => 'tenant', 'aria-describedby' => 'help', 'x-ref' => 'editor'] as $name => $expected) {
        expect($editor->getAttribute($name))->toBe($expected);
    }
});

it('scopes default settings to the nearest Blade group', function () {
    $dom = HTMLDocument::createFromString(Blade::render(<<<'BLADE'
        <x-ui::accordion multiple><x-ui::accordion.item><x-ui::accordion>
            <x-ui::accordion.item id="a" /><x-ui::accordion.item id="b" />
        </x-ui::accordion></x-ui::accordion.item></x-ui::accordion>
        <x-ui::toggle-group type="multiple"><x-ui::toggle-group id="inner"><x-ui::toggle-group.item value="a" /></x-ui::toggle-group></x-ui::toggle-group>
        <x-ui::progress :value="1" :max="4"><x-ui::progress><x-ui::progress.value id="progress-text" /></x-ui::progress></x-ui::progress>
        <x-ui::checkbox wire:model="roles" value="a" /><x-ui::checkbox wire:model="roles" value="b" />
        <x-ui::input wire:model="profile.code" /><x-ui::input wire:model="profile_code" />
        BLADE), LIBXML_NOERROR);
    expect($dom->getElementById('a')->getAttribute('name'))->not->toBeEmpty()
        ->and($dom->getElementById('a')->getAttribute('name'))->toBe($dom->getElementById('b')->getAttribute('name'))
        ->and($dom->querySelector('#inner input')->getAttribute('type'))->toBe('radio')
        ->and($dom->getElementById('progress-text')->textContent)->toBe('');
    $ids = array_map(fn ($input) => $input->id, iterator_to_array($dom->querySelectorAll('input[id]')));
    expect($ids)->toHaveCount(4)->and(array_unique($ids))->toHaveCount(4);
});

it('removes only the requested occurrence of an existing duplicate tag', function () {
    Livewire::test(TagsInput::class, ['initialValue' => ['same', 'middle', 'same']])
        ->call('remove', 'same', 2)->assertSet('value', ['same', 'middle'])
        ->call('remove', 'same', 2)->assertSet('value', ['same', 'middle']);
});
