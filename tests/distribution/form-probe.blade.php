<?php

use Livewire\Component;

new class extends Component {
    public ?string $role = 'a';
    public ?string $deferredRole = 'a';
    public ?string $saved = null;
    public array $options = [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']];
    public bool $firstTab = true;
    public bool $verticalTabs = true;
    public string $tab = 'a';
    public int $amount = 10;

    public function save(): void
    {
        $this->saved = $this->role;
    }

    public function saveDeferred(): void
    {
        $this->saved = $this->deferredRole;
    }

    public function removeAlpha(): void
    {
        $this->options = array_slice($this->options, 1);
    }
};
?>
<main>
    <form id="live-form" wire:submit="save">
        <x-ui::select id="role" name="role" wire:model.live="role" :options="$options" required placeholder="Pick a role" />
        <button type="submit">Save role</button>
        <button type="reset">Reset role</button>
    </form>
    <form wire:submit="saveDeferred">
        <x-ui::select id="deferred-role" wire:model="deferredRole" :options="$options" />
        <button type="submit">Save deferred role</button>
    </form>
    <output id="role-value">{{ $role }}</output>
    <output id="saved-role">{{ $saved }}</output>
    <button type="button" wire:click="removeAlpha">Remove Alpha</button>

    <form id="native-form" action="/ui-form-result" target="native-result">
        <button type="submit">Send native</button>
        <button type="reset">Reset native</button>
    </form>
    <iframe name="native-result" title="Native submission" hidden></iframe>
    <x-ui::select id="native-role" form="native-form" name="role" initial-value="a" :options="$options" />
    <x-ui::select id="multi-role" form="native-form" name="roles" multiple required :initial-value="[0]"
        :options="[['value' => '', 'label' => 'Empty string'], ['value' => 0, 'label' => 'Zero'], ['value' => 'b', 'label' => 'Beta']]" />
    <div id="requested" x-data x-on:value-changed="$el.dataset.requested = JSON.stringify($event.detail.value)">
        <x-ui::select id="controlled-role" form="native-form" name="controlled" :value="$role" :options="$options" />
    </div>

    <form id="slider-form">
        <x-ui::slider wire:model="amount" name="amount" :max="10" :step="4" />
        <x-ui::slider id="decimal-slider" name="fraction" orientation="vertical" inverted :min="-0.2" :max="0.35" :step="0.1" :initial-value="0.1" />
        <x-ui::slider id="multi-slider" name="span" :max="10" :step="4" :initial-value="[3, 9]" />
    </form>
    <x-ui::tabs id="changing-tabs" wire:model.live="tab" :orientation="$verticalTabs ? 'vertical' : 'horizontal'">
        <x-ui::tabs.list wire:key="tabs-list-{{ (int) $verticalTabs }}">
            @if ($firstTab)<x-ui::tabs.tab value="a">First tab</x-ui::tabs.tab>@endif
            <x-ui::tabs.tab value="b">Second tab</x-ui::tabs.tab>
        </x-ui::tabs.list>
        @if ($firstTab)
            <x-ui::tabs.content value="a">
                <x-ui::tabs id="inner-tabs" dir="rtl">
                    <x-ui::tabs.list variant="line" activate-on-focus>
                        <x-ui::tabs.tab value="a">Inner first</x-ui::tabs.tab>
                        <x-ui::tabs.tab value="disabled" disabled>Unavailable</x-ui::tabs.tab>
                        <x-ui::tabs.tab value="b">Inner second</x-ui::tabs.tab>
                    </x-ui::tabs.list>
                    <x-ui::tabs.content value="a">Inner first panel</x-ui::tabs.content>
                    <x-ui::tabs.content value="b">Inner second panel</x-ui::tabs.content>
                </x-ui::tabs>
            </x-ui::tabs.content>
        @endif
        <x-ui::tabs.content value="b">Second panel</x-ui::tabs.content>
    </x-ui::tabs>
    <button type="button" wire:click="$toggle('firstTab')">Toggle first tab</button>
    <button type="button" wire:click="$toggle('verticalTabs')">Rotate tabs</button>
    <output id="active-tab">{{ $tab }}</output>
    <x-ui::questionnaire id="survey" shortcuts="letters" :items="[['name' => 'answer', 'prompt' => 'Answer',
        'choices' => [['value' => 'a', 'label' => 'Option A']], 'input' => ['label' => 'Free text']]]" />
</main>
