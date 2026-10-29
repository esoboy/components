@blaze

@use('Esoboy\Ui\Assets')
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')
@use('Esoboy\Ui\Support\Overlay')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: a Tiptap editor framed as its Textarea. format (text, basic, full, a named format or a list of features) decides all it does: what can be written and what pasted HTML keeps (its whitelist), and the tools it offers; the toolbar fits it unless the slot holds an <x-ui::editor.toolbar>. The HTML is sanitized to the format on the way in here; put #[Sanitize] with the same format on the property for the way back. Images on hosts other than the page's and image-hosts, and data: ones, are the app's to save when someone asks (editor-external-images, editor-data-images). --}}
@props([
    'format' => 'full',
    'maxLength' => null,
    'imageHosts' => [],
    'placeholder' => null,
    'label' => null,
    'defaultValue' => null,
    'disabled' => false,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$rich = RichText::resolve($format);
$value = $defaultValue ?? Model::value($attributes);
$value = $rich->sanitize(is_string($value) ? $value : '');
$name = $attributes->get('name');
$counts = $rich->has('character-count') || $maxLength;
$tip = Overlay::open('editor-tip');
Overlay::close('editor-tip');

// The Emoji Picker's data, for :shortcode: suggestions.
$assets = app(Assets::class);
$locale = strtolower(str_replace('_', '-', app()->getLocale()));
$emoji = $rich->has('emoji') ? $assets->url(collect(["emoji/{$locale}.json", 'emoji/'.strtok($locale, '-').'.json', 'emoji/en.json'])->first(fn (string $file) => $assets->path($file) !== null, 'emoji/en.json')) : null;
@endphp

@assets
{!! app(Assets::class)->module('editor.js') !!}
@endassets

{{-- <eso-editor> exposes the HTML as .value ('' when empty), so wire:model binds on it. --}}
<eso-editor {{ $attributes->except('name')->merge([
    'data-slot' => 'editor',
    'data-format' => json_encode($rich->client(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'data-labels' => json_encode(['expand' => __('ui::editor.expand-details'), 'collapse' => __('ui::editor.collapse-details'), 'task' => __('ui::editor.task-checkbox'), 'caption' => __('ui::editor.caption-placeholder'), 'keyboard' => $rich->has('toolbar', 'bubble-menu') ? __('ui::editor.keyboard-hint') : ''], JSON_UNESCAPED_UNICODE),
    'data-image-hosts' => $imageHosts ? implode(' ', $imageHosts) : false,
    'data-max-length' => $maxLength ?? false,
    'data-emoji' => $emoji ?? false,
    'data-placeholder' => $placeholder ?? false,
    'data-label' => $label ?? __('ui::editor.label'),
    'data-name' => $name ?? false,
    'data-disabled' => (bool) $disabled,
    ...$state->attributes(),
]) }}>
    @if (! $slot->isEmpty())
    {{ $slot }}
    @elseif ($rich->has('toolbar'))
    <x-ui::editor.toolbar />
    @endif
    @if ($rich->has('find-replace'))
    <x-ui::editor.find />
    @endif
    <div data-slot="editor-content" wire:ignore>{!! $value !!}</div>
    @if ($rich->has('source'))
    <textarea data-slot="editor-source" aria-label="{{ __('ui::editor.source') }}" spellcheck="false" hidden wire:ignore></textarea>
    @endif
    @if ($counts || $rich->has('image'))
    {{-- Images the app has no copy of, to save them all; the count. --}}
    <div data-slot="editor-footer" wire:ignore>
        @if ($rich->has('image'))
        <span data-editor-unsaved data-editor-label="{{ __('ui::editor.images-unsaved') }}" hidden><span data-editor-unsaved-label></span><x-ui::button variant="link" size="xs" data-editor-action="save-images">{{ __('ui::editor.save-images') }}</x-ui::button></span>
        @endif
        @if ($counts)
        <span data-editor-characters><span data-editor-count></span> {{ __('ui::editor.characters') }}</span>
        @endif
    </div>
    @endif
    <x-ui::editor.menus />
    <div id="{{ $tip }}" popover="manual" role="tooltip" data-slot="tooltip-content" data-side="top" data-align="center" data-editor-tip style="position-anchor: --{{ $tip }}; --side-offset: 4px" wire:ignore><span data-editor-tip-label></span><x-ui::kbd data-editor-tip-keys hidden /></div>
    @if ($name)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
</eso-editor>
<x-ui::field.error :state="$state" />
