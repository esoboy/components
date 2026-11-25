<?php

use Livewire\Component;

new class extends Component {
    public int $buttonVersion = 0;
    public int $panelVersion = 0;
    public bool $hasPanel = true;
    public int $sliderVersion = 1;
    public array $defaults = [20, 80];
    public bool $locked = false;
    public string $item = 'single';
    public ?string $icon = null;

    public function updatedIcon(?string $value): void
    {
        if ($value === 'zoom-in') {
            $this->icon = 'star';
        }
    }
};
?>
<main>
    <button id="replace-trigger" wire:click="$set('buttonVersion', 1)" type="button">Replace trigger</button>
    <x-ui::media-preview id="state-preview" :type="$buttonVersion ? 'image' : 'audio'"
        :src="$buttonVersion ? 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' : 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQQAAACAgICA'">
        @if ($buttonVersion)
            <x-ui::button><x-ui::icon name="image" />View picture</x-ui::button>
        @else
            Listen
        @endif
    </x-ui::media-preview>
    <x-ui::dialog id="state-dialog">
        <x-ui::dialog.trigger><x-ui::button wire:key="trigger-{{ $buttonVersion }}" id="trigger-{{ $buttonVersion }}">Open</x-ui::button></x-ui::dialog.trigger>
        @if ($hasPanel)
            <x-ui::dialog.content wire:key="panel-{{ $panelVersion }}" aria-label="Details">
                <button id="change-open-trigger" type="button" wire:click="$set('buttonVersion', 2)">Replace open trigger</button>
                <button id="panel-{{ $panelVersion }}" type="button" wire:click="$set('panelVersion', 1)">Replace panel</button>
                <button id="remove-panel" type="button" wire:click="$set('hasPanel', false)">Remove panel</button>
            </x-ui::dialog.content>
        @endif
    </x-ui::dialog>
    <form id="ranges"></form>
    <x-ui::slider :wire:key="'range-'.$sliderVersion" id="range" name="range" form="ranges" :initial-value="$defaults" :readonly="$locked" />
    <button id="defaults" type="button" wire:click="$set('defaults', [5])">Change defaults</button>
    <button id="remount" type="button" wire:click="$set('sliderVersion', 2)">Remount</button>
    <button id="lock" type="button" wire:click="$toggle('locked')">Toggle readonly</button>
    <button type="button" wire:click="$set('icon', 'circle-check')">Choose an icon</button>
    @if ($icon !== null)
        <form id="icon-form">
            <x-ui::icon-select name="icon" wire:model.live="icon" auto-highlight aria-label="Icon" />
            <output id="chosen-icon">{{ $icon }}</output>
        </form>
    @endif
    <form id="external"></form>
    <x-ui::editor id="external-editor" form="external" name="body" initial-value="First" input-type="markdown" output-type="markdown" />
    @php($drawingImage = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="20"><path fill="red" d="M0 0h20v20H0z"/><path fill="blue" d="M20 0h20v20H20z"/></svg>'))
    <form id="external-drawings"></form>
    <div x-data style="width:240px">
        <x-ui::cropper id="export-crop" x-ref="crop" form="external-drawings" name="crop" :src="$drawingImage"
            :initial-value="['x' => 0, 'y' => 0, 'width' => .5, 'height' => 1]" :readonly="$locked" />
        <x-ui::signature id="export-signature" x-ref="signature" form="external-drawings" name="signature" :readonly="$locked" />
        <button id="read-drawings" type="button" x-on:click="$el.textContent = [(await $refs.crop.getResult()).type, (await $refs.signature.getResult())?.type].join(',')">Read drawings</button>
    </div>
    <x-ui::rating form="external" name="score" :initial-value="4" :readonly="$locked" />
    <x-ui::select form="external" name="team" :initial-value="0" :options="[['value' => 0, 'label' => 'Zero']]" />
    <x-ui::combobox form="external" name="members" multiple :initial-value="['a']" :options="[['value' => 'a', 'label' => 'Alpha']]" />
    <form id="readonly-survey">
    <x-ui::questionnaire id="survey-fields" readonly :item="$item"
        :items="[['name' => 'single', 'prompt' => 'One', 'choices' => [['value' => 'a', 'label' => 'Alpha']]], ['name' => 'multi', 'prompt' => 'Two', 'multiple' => true, 'choices' => [['value' => 'b', 'label' => 'Beta']], 'input' => ['label' => 'Other']]]"
        :initial-value="['single' => ['choices' => ['a']], 'multi' => ['choices' => ['b'], 'text' => 'Other answer']]" />
    </form>
    <button id="next-item" type="button" wire:click="$set('item', 'multi')">Second question</button>
    <div style="position:fixed;left:140px;top:100px">
        <x-ui::dropdown-menu>
            <x-ui::dropdown-menu.trigger><x-ui::button id="side-trigger" style="width:80px">Menu</x-ui::button></x-ui::dropdown-menu.trigger>
            <x-ui::dropdown-menu.content side="right" style="width:200px">
                <x-ui::dropdown-menu.sub>
                    <x-ui::dropdown-menu.sub-trigger><x-ui::button>Options</x-ui::button></x-ui::dropdown-menu.sub-trigger>
                    <x-ui::dropdown-menu.sub-content style="width:220px"><x-ui::dropdown-menu.item>Option</x-ui::dropdown-menu.item></x-ui::dropdown-menu.sub-content>
                </x-ui::dropdown-menu.sub>
            </x-ui::dropdown-menu.content>
        </x-ui::dropdown-menu>
    </div>
</main>
