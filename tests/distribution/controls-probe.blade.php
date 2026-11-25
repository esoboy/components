<?php

use Livewire\Component;

new class extends Component {
    public int $quantity = 0;
    public string $code = '';
    public bool $showPhoto = true;
    public ?string $action = null;
};
?>
<main>
    <form id="step-form">
        <x-ui::number-input name="quantity" aria-label="Quantity" wire:model.live="quantity" :min="0" :max="10" :step="3" />
        <output id="step-value">{{ $quantity }}</output>
    </form>
    <x-ui::input-otp id="code" name="code" wire:model.live="code" :length="6" />
    <output id="saved-code">{{ $code }}</output>

    <x-ui::command id="actions" wire:model.live="action" :options="[
        ['value' => 'none', 'label' => 'Unavailable', 'disabled' => true],
        ['value' => 'read', 'label' => 'Reader', 'group' => 'Roles'],
        ['value' => 'edit', 'label' => 'Editor', 'group' => 'Roles'],
    ]" />
    <output id="chosen-action">{{ $action }}</output>
    <x-ui::avatar id="photo">
        @if($showPhoto)<x-ui::avatar.image src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" alt="Photo" />@endif
        <x-ui::avatar.fallback>AB</x-ui::avatar.fallback>
    </x-ui::avatar>
    <button type="button" wire:click="$toggle('showPhoto')">Toggle photo</button>

    <x-ui::resizable id="split" style="width:600px;max-width:100%;height:48px">
        <x-ui::resizable.panel>Left</x-ui::resizable.panel><x-ui::resizable.handle aria-label="First split" />
        <x-ui::resizable.panel>Middle</x-ui::resizable.panel><x-ui::resizable.handle aria-label="Second split" />
        <x-ui::resizable.panel>Right</x-ui::resizable.panel>
    </x-ui::resizable>
    <x-ui::radio-group><x-ui::radio-group.item id="custom-radio" name="plan" value="one" checked style="--item-checked-bg:var(--color-primary-foreground);--part-fg:#a12230" /></x-ui::radio-group>
    <x-ui::select class="w-full" id="full-select" aria-label="Role" :options="[['value' => 'read', 'label' => 'Reader']]" />

    <button type="button" id="before-notices">Before notices</button>
    <x-ui::toaster id="notices">
        @foreach(['First', 'Last'] as $notice)
            <x-ui::toast :duration="0"><x-ui::toast.content><x-ui::toast.title>{{ $notice }}</x-ui::toast.title>
                <x-ui::toast.close><button type="button">Dismiss {{ $notice }}</button></x-ui::toast.close>
            </x-ui::toast.content></x-ui::toast>
        @endforeach
    </x-ui::toaster>
    <button type="button" id="after-notices">After notices</button>
    <section style="transform:translateZ(0)">
        <x-ui::sidebar id="editor-sidebar"><x-ui::sidebar.content>Navigation</x-ui::sidebar.content>
            <x-ui::sidebar.inset><x-ui::sidebar.trigger><button type="button">Toggle sidebar</button></x-ui::sidebar.trigger>
                <x-ui::editor id="bold-editor" initial-value="Edit here" input-type="markdown" />
            </x-ui::sidebar.inset>
        </x-ui::sidebar>
    </section>
</main>
