@blaze

@use('Esoboy\Ui\Assets')
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: a Tiptap editor framed as its Textarea, with a toolbar (the whole one unless the slot holds an <x-ui::editor.toolbar>). The HTML is sanitized on the way in here; put #[Sanitize] on the property for the way back. --}}
@props([
    'placeholder' => null,
    'label' => null,
    'defaultValue' => null,
    'disabled' => false,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? Model::value($attributes);
$value = RichText::sanitize(is_string($value) ? $value : null);
$name = $attributes->get('name');
@endphp

@assets
{!! app(Assets::class)->module('editor.js') !!}
@endassets

{{-- <eso-editor> exposes the HTML as .value ('' when empty), so wire:model binds on it. --}}
<eso-editor {{ $attributes->except('name')->merge(['data-slot' => 'editor', 'data-placeholder' => $placeholder ?? false, 'data-label' => $label ?? __('ui::editor.label'), 'data-name' => $name ?? false, 'data-disabled' => (bool) $disabled, ...$state->attributes()]) }}>
    @if ($slot->isEmpty())
    <x-ui::editor.toolbar />
    @else
    {{ $slot }}
    @endif
    <div data-slot="editor-content" wire:ignore>{!! $value !!}</div>
    @if ($name)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
</eso-editor>
<x-ui::field.error :state="$state" />
