<?php

use Livewire\Component;

new class extends Component {
    public bool $insert = false;
    public bool $reversed = false;
    public int $length = 6;
    public string $tab = 'b';
    public int $changes = 0;
    public array $layout = [];
    public array $booleanRows = [];
    public int $acceptedNumber = 5;
    public int $numberRequests = 0;
    public string $draft = '';
    public array $acceptedRoles = ['a'];

    public function considerNumber(?int $value): void
    {
        $this->numberRequests++;
        if ($value === 8) $this->acceptedNumber = $value;
    }

    public function countChange(): void
    {
        $this->changes++;
    }
};
?>
<main>
    @if ($insert)<x-ui::tags-input class="inserted" :initial-value="['new']" />@endif
    <x-ui::tags-input class="original" :initial-value="['original']" />
    <button id="insert" wire:click="$set('insert', true)">Insert</button>
    @foreach ($reversed ? ['b', 'a'] : ['a', 'b'] as $field)
        <x-ui::password-input :wire:key="'password-'.$field" :id="'password-'.$field" />
    @endforeach
    <x-ui::select id="accepted-roles" multiple :value="$acceptedRoles" :options="[['value'=>'a','label'=>'Alpha']]" wire:value-changed="$set('acceptedRoles', $event.detail.value)" />
    <output id="roles-value">{{ json_encode($acceptedRoles) }}</output>
    <x-ui::toaster>
        @foreach ($reversed ? ['b', 'a'] : ['a', 'b'] as $field)
            <x-ui::toast :wire:key="'notice-'.$field" :id="'notice-'.$field" :duration="0">
                <x-ui::toast.content><x-ui::toast.close><button type="button">Dismiss {{ $field }}</button></x-ui::toast.close></x-ui::toast.content>
            </x-ui::toast>
        @endforeach
    </x-ui::toaster>
    <button id="reverse-fields" wire:click="$toggle('reversed')">Reverse</button>
    <x-ui::tabs id="controlled-tabs" :value="$tab" wire:value-changed="countChange">
        <x-ui::tabs.list><x-ui::tabs.tab value="a" id="custom-tab">Alpha</x-ui::tabs.tab><x-ui::tabs.tab value="b">Beta</x-ui::tabs.tab></x-ui::tabs.list>
        <x-ui::tabs.content value="a" id="custom-panel">A</x-ui::tabs.content><x-ui::tabs.content value="b">B</x-ui::tabs.content>
    </x-ui::tabs>
    <x-ui::select id="listened" :value="$tab" wire:value-changed="countChange" :options="[['value'=>'a','label'=>'Option A'],['value'=>'b','label'=>'Option B']]" />
    <output id="change-count">{{ $changes }}</output>
    <x-ui::data-table id="first-table" :rows="[['id'=>1]]" :columns="['id'=>['label'=>'ID']]" layout-model="layout">
        <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
    </x-ui::data-table>
    <x-ui::data-table id="second-table" :rows="[['id'=>1]]" :columns="['id'=>['label'=>'ID']]" layout-model="layout">
        <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
    </x-ui::data-table>
    <x-ui::tabs id="numeric-tabs" :value="2">
        <x-ui::tabs.list><x-ui::tabs.tab :value="1">One</x-ui::tabs.tab><x-ui::tabs.tab :value="2">Two</x-ui::tabs.tab></x-ui::tabs.list>
        <x-ui::tabs.content :value="1">Panel one</x-ui::tabs.content><x-ui::tabs.content :value="2">Panel two</x-ui::tabs.content>
    </x-ui::tabs>
    <x-ui::data-table id="boolean-rows" :rows="[['id'=>1],['id'=>0]]" :columns="['id'=>['label'=>'ID']]" wire:model.boolean.live="booleanRows" />
    <form id="survey" method="get" action="/ui-package-test">
        <input type="hidden" name="submittedChecks" value="1">
        <x-ui::questionnaire :items="[['name'=>'picks','prompt'=>'Pick two','multiple'=>true,'choices'=>[['value'=>'a','label'=>'Pick A'],['value'=>'b','label'=>'Pick B']]]]" />
        <x-ui::questionnaire :items="[['name'=>'note','prompt'=>'Write text','input'=>['label'=>'Free text']]]" />
        <button type="submit">Send answers</button>
    </form>
    <form id="proposed-quantity">
        <x-ui::number-input name="quantity" :min="1" :max="10" :value="$acceptedNumber" wire:value-changed="considerNumber($event.detail.value)" />
        <output>{{ $numberRequests }}</output>
    </form>
    <form id="digits"><x-ui::input-otp name="otp" :length="$length" /></form>
    <button id="shorten" wire:click="$set('length', 4)">Use four digits</button>
    <form id="external-reset"></form>
    <x-ui::tags-input name="tags" form="external-reset" required placeholder="Add a tag" :initial-value="['first']" />
    <span id="draft-label">Article</span><span id="draft-help">Write here</span>
    <form id="editor-check"><x-ui::editor id="draft" name="draft" wire:model="draft" output-type="markdown" required
        aria-labelledby="draft-label" aria-describedby="draft-help" /></form>
</main>
