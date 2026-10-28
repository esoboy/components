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

{{-- Not in shadcn: a Tiptap editor framed as its Textarea. format (text, basic, full, a named format or a list of features) decides what can be written, tools what it offers besides; the toolbar fits both unless the slot holds an <x-ui::editor.toolbar>. The HTML is sanitized to the format on the way in here; put #[Sanitize] with the same format on the property for the way back. --}}
@props([
    'format' => 'full',
    'tools' => null,
    'maxLength' => null,
    'placeholder' => null,
    'label' => null,
    'defaultValue' => null,
    'disabled' => false,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$rich = RichText::resolve($format);
$tools = $rich->tools($tools);
$value = $defaultValue ?? Model::value($attributes);
$value = $rich->sanitize(is_string($value) ? $value : '');
$name = $attributes->get('name');
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
    'data-tools' => implode(' ', $tools),
    'data-labels' => json_encode(['expand' => __('ui::editor.expand-details'), 'collapse' => __('ui::editor.collapse-details'), 'task' => __('ui::editor.task-checkbox')], JSON_UNESCAPED_UNICODE),
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
    @elseif (in_array('toolbar', $tools, true))
    <x-ui::editor.toolbar />
    @endif
    @if (in_array('find-replace', $tools, true))
    <x-ui::editor.find />
    @endif
    <div data-slot="editor-content" wire:ignore>{!! $value !!}</div>
    @if (in_array('source', $tools, true))
    <textarea data-slot="editor-source" aria-label="{{ __('ui::editor.source') }}" spellcheck="false" hidden wire:ignore></textarea>
    @endif
    @if (in_array('character-count', $tools, true))
    <div data-slot="editor-footer" wire:ignore><span data-editor-count></span></div>
    @endif
    <x-ui::editor.menus />
    <div id="{{ $tip }}" popover="manual" role="tooltip" data-slot="tooltip-content" data-side="top" data-align="center" data-editor-tip style="position-anchor: --{{ $tip }}; --side-offset: 4px" wire:ignore><span data-editor-tip-label></span><x-ui::kbd data-editor-tip-keys hidden /></div>
    @if ($name)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
</eso-editor>
<x-ui::field.error :state="$state" />
