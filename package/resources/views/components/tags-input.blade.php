@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Enter or a comma adds what was typed, Backspace in the empty input removes the last tag. --}}
@props([
    'placeholder' => null,
    'max' => null,
    'disabled' => false,
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$tags = array_values(array_map('strval', (array) ($defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes) ?? [])));
$name = $attributes->get('name');
@endphp

{{-- <eso-tags-input> exposes the tags as .value, so wire:model binds on it. --}}
<eso-tags-input {{ $attributes->except('name')->merge([
    'data-slot' => 'tags-input',
    'data-value' => json_encode($tags),
    'data-max' => $max ?? false,
    'data-name' => $name ?? false,
    'data-disabled' => (bool) $disabled,
]) }}>
    <template data-tags-input-template>
        <span data-slot="tags-input-tag">
            <span data-tags-input-label></span>
            <x-ui::button variant="ghost" size="icon-xs" data-tags-input-remove :aria-label="__('ui.remove')" tabindex="-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </x-ui::button>
        </span>
    </template>
    @foreach ($tags as $tag)
    <span data-slot="tags-input-tag" wire:key="tag-{{ $loop->index }}"><span data-tags-input-label>{{ $tag }}</span></span>
    @endforeach
    <input type="text" data-slot="tags-input-input" placeholder="{{ $placeholder }}" autocomplete="off" {{ new \Illuminate\View\ComponentAttributeBag($state->attributes()) }} @if ($disabled) disabled @endif>
    @if ($name)
        @foreach ($tags as $tag)
        <input type="hidden" name="{{ $name }}[]" value="{{ $tag }}">
        @endforeach
    @endif
</eso-tags-input>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
