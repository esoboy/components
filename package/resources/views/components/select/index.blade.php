@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')
@use('Esoboy\Ui\Support\ChoiceMarkup')
@use('Esoboy\Ui\Support\Options')

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'value' => null,
    'options' => null,
    'placeholder' => null,
    'disabled' => false,
    'size' => 'default',
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value);
$name = $attributes->get('name');
@endphp

@if ($options !== null)
    @php ob_start(); @endphp
    <x-ui::select.trigger :size="$size" :disabled="$disabled"><x-ui::select.value :placeholder="$placeholder" /></x-ui::select.trigger>
    <x-ui::select.content><x-ui::select.group>
        @foreach (Options::normalize($options) as $option)
            <x-ui::select.item :value="$option['value']" :disabled="! empty($option['disabled'])">{{ $option['label'] }}</x-ui::select.item>
        @endforeach
    </x-ui::select.group></x-ui::select.content>
    @php $content = ob_get_clean(); @endphp
@else
    @php $content = (string) $slot; @endphp
@endif
@php $markup = new ChoiceMarkup($content); @endphp

<eso-select {{ $attributes->whereDoesntStartWith('wire:model')->except('name')->merge([
    'data-slot' => 'select',
    'data-value' => is_scalar($value) ? (string) $value : false,
    'data-name' => $name ?? false,
    'x-data' => 'uiSelect('.json_encode(Model::binding($attributes)).')',
    'x-effect' => 'render()',
    'x-on:click' => 'click($event)',
    'x-on:keydown' => 'key($event)',
    'x-on:toggle.capture' => 'toggle($event)',
    'x-on:scroll.document.capture.passive' => 'align($event)',
    'x-on:resize.window' => 'align()',
    'x-on:focusout' => '!$el.contains($event.relatedTarget) && blur()',
    ...$state->attributes(),
]) }}>
    {!! $markup->render($value, $state->attributes(), $disabled) !!}
    <select hidden x-ref="input" aria-hidden="true" tabindex="-1" {{ $attributes->whereStartsWith('wire:model') }} @if ($name) name="{{ $name }}" @endif @disabled($disabled)>
        <option value=""></option>
        @foreach ($markup->options() as $option)
            <option value="{{ $option['value'] }}" @selected((string) $value === $option['value']) @disabled($option['disabled'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
</eso-select>
<x-ui::field.error :state="$state" />
