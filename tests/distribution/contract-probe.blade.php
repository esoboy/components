<?php

use Livewire\Component;

new class extends Component {
    public int $first = 4;
    public int $second = 8;
    public bool $switched = false;
    public array $rows = ['one', 'two'];
    public ?string $choice = 'a';
    public string $radio = 'a';
    public array $toggles = ['a'];
    public ?array $selection = null;
    public array $numericSelection = [9];
    public bool $editors = false;
    public string $oldDraft = 'First';
    public string $newDraft = 'Second';

    public function reverseRows(): void
    {
        $this->rows = array_reverse($this->rows);
    }

    public function save(): void {}
};
?>
<main>
    @php($options = [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']])
    <x-ui::select :options="$options" /><x-ui::select :options="$options" />
    <x-ui::number-input id="moving" :wire:model="$switched ? 'second' : 'first'" />
    <button id="switch" wire:click="$toggle('switched')">Switch</button>
    <button id="update-first" wire:click="$set('first', 13)">Change first</button>
    <button id="update-second" wire:click="$set('second', 15)">Change second</button>
    <form id="before"></form><form id="after"></form>
    <x-ui::select id="owned" :form="$switched ? 'after' : 'before'" :name="$switched ? 'next' : 'previous'"
        :aria-label="$switched ? 'New label' : 'Old label'" initial-value="b" :options="$options" />
    @if($switched)<x-ui::select id="source" wire:model="choice" :options="$options" />
    @else<x-ui::select id="source" value="b" :options="$options" />@endif
    @foreach($rows as $row)
        <x-ui::number-input :wire:key="$row" :id="'row-'.$row" :initial-value="$row === 'one' ? 1 : 2" />
    @endforeach
    <button id="reverse" wire:click="reverseRows">Reverse</button>
    <x-ui::radio-group id="radio" wire:model.live="radio">
        <x-ui::radio-group.item value="a" /><x-ui::radio-group.item value="b" />
    </x-ui::radio-group>
    <x-ui::toggle-group id="toggles" type="multiple" wire:model.live="toggles">
        <x-ui::toggle-group.item value="a">A</x-ui::toggle-group.item><x-ui::toggle-group.item value="b">B</x-ui::toggle-group.item>
    </x-ui::toggle-group>
    <output id="group-values">{{ $radio }}|{{ implode(',', $toggles) }}</output>
    <x-ui::number-input id="loading" wire:loading.attr="disabled" wire:target="save" />
    <form id="outer" wire:submit="save">
        <x-ui::questionnaire :items="[['name' => 'answer', 'prompt' => 'Answer', 'choices' => [['value' => 'a', 'label' => 'Yes']]]]" />
        <button id="save">Save</button>
    </form>
    <x-ui::data-table id="nullable" :rows="[['id'=>'one','title'=>'One']]"
        :columns="['title' => ['label'=>'Title']]" wire:model="selection" />
    <x-ui::data-table id="numeric-table" :rows="[['id'=>1], ['id'=>2]]"
        :columns="['id' => ['label'=>'ID']]" wire:model.number.blur.live="numericSelection" />
    <output id="numeric-selection">{{ json_encode($numericSelection) }}</output>
    <button id="enable-editors" wire:click="$set('editors', true)">Edit drafts</button>
    @if($editors)
        <x-ui::editor id="moving-editor" :wire:model="$switched ? 'newDraft' : 'oldDraft'" output-type="markdown" />
        <x-ui::editor id="keyed-editor" wire:key="keyed-draft" :wire:model="$switched ? 'newDraft' : 'oldDraft'" output-type="markdown">
            <span>Shared draft</span>
        </x-ui::editor>
    @endif
</main>
