<?php

use Livewire\Component;

new class extends Component {
    public string $accent = '#2563eb';
    public string $palette = 'mauve-300';
};
?>
<main style="--brand: #15803d; --local-ink: #007e51">
    <button id="change-color" type="button" wire:click="$set('accent', '#c026d3')">Change color</button>
    <x-ui::button id="solid" :color="$accent">Save</x-ui::button>
    <x-ui::button id="outline" variant="outline" color="var(--brand)">Preview</x-ui::button>
    <x-ui::button id="default">Default</x-ui::button>
    <x-ui::button id="light" color="#ffff00">Light fill</x-ui::button>
    <x-ui::button id="palette" :color="$palette">Palette</x-ui::button>
    <x-ui::button id="family" color="teal">Family</x-ui::button>
    <x-ui::button id="brand" color="store-brand">Store</x-ui::button>
    <x-ui::badge id="inline-brand" color="local-brand">Local</x-ui::badge>
    <x-ui::button id="opacity" color="store-brand/50">Translucent</x-ui::button>
    <button id="change-palette" type="button" wire:click="$set('palette', 'campaign-700')">Change palette</button>
    <x-ui::alert :color="$accent"><x-ui::alert.title>Notice</x-ui::alert.title><x-ui::alert.action><x-ui::button id="slot-button">Action</x-ui::button></x-ui::alert.action></x-ui::alert>
    <x-ui::avatar><x-ui::avatar.fallback :color="$accent">UI</x-ui::avatar.fallback><x-ui::avatar.badge color="var(--brand)" /></x-ui::avatar>
    <x-ui::checkbox :color="$accent" indeterminate aria-label="Some selected" />
    <x-ui::radio-group :color="$accent"><label><x-ui::radio-group.item checked name="one" /></label><x-ui::radio-group.item color="var(--brand)" checked name="two" /></x-ui::radio-group>
    <x-ui::switch :color="$accent" checked aria-label="On" />
    <x-ui::toggle-group :color="$accent"><x-ui::toggle-group.item selected name="style" value="bold">Bold</x-ui::toggle-group.item></x-ui::toggle-group>
    <x-ui::progress :color="$accent" :value="70" aria-label="Progress" />
    <x-ui::slider id="colored-slider" :color="$accent" :initial-value="40" />
    <x-ui::rating id="colored-rating" :color="$accent" :initial-value="2" />
    <x-ui::steps :color="$accent" :current="2" :items="[['title' => 'One'], ['title' => 'Two']]" />
    <x-ui::timeline :color="$accent"><x-ui::timeline.item aria-current="step">Now</x-ui::timeline.item><x-ui::timeline.item color="var(--brand)">Later</x-ui::timeline.item></x-ui::timeline>
    <x-ui::tabs :color="$accent" initial-value="one"><x-ui::tabs.list variant="line"><x-ui::tabs.tab value="one">One</x-ui::tabs.tab></x-ui::tabs.list><x-ui::tabs.content value="one">Content</x-ui::tabs.content></x-ui::tabs>
    <x-ui::qr-code :color="$accent" value="test" />
    <x-ui::signature id="colored-signature" :color="$accent" :initial-value="[[['x' => 0.2, 'y' => 0.5], ['x' => 0.8, 'y' => 0.5]]]" />
    <x-ui::select id="colored-select" :color="$accent" initial-value="a" :options="[['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']]" />
    <x-ui::tags-input id="colored-tags" :color="$accent" :initial-value="['blue']" />
    <x-ui::tree id="colored-tree" :color="$accent" initial-value="leaf" :nodes="[['value' => 'leaf', 'label' => 'Leaf']]" />
    <x-ui::date-picker id="colored-date" range :initial-value="['2026-11-10', '2026-11-12']" :color="$accent" />
    <x-ui::time-picker id="colored-time" initial-value="09:30" color="var(--local)" style="--local: #15803d" />
</main>
