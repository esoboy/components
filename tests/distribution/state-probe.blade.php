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
    <form id="external"></form>
    <x-ui::editor id="external-editor" form="external" name="body" initial-value="First" input-type="markdown" output-type="markdown" />
    <x-ui::cropper form="external" name="crop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" />
    <x-ui::signature form="external" name="signature" />
    <x-ui::rating form="external" name="score" :initial-value="4" :readonly="$locked" />
    <x-ui::select form="external" name="team" :initial-value="0" :options="[['value' => 0, 'label' => 'Zero']]" />
    <x-ui::combobox form="external" name="members" multiple :initial-value="['a']" :options="[['value' => 'a', 'label' => 'Alpha']]" />
    <x-ui::questionnaire id="readonly-survey" readonly :item="$item"
        :items="[['name' => 'single', 'prompt' => 'One', 'choices' => [['value' => 'a', 'label' => 'Alpha']]], ['name' => 'multi', 'prompt' => 'Two', 'multiple' => true, 'choices' => [['value' => 'b', 'label' => 'Beta']], 'input' => ['label' => 'Other']]]"
        :initial-value="['single' => ['choices' => ['a']], 'multi' => ['choices' => ['b'], 'text' => 'Other answer']]" />
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
