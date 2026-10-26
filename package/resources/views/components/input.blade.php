@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'type' => 'text',
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
@endphp

<input {{ $attributes->merge(['type' => $type, 'data-slot' => 'input', ...$state->attributes()]) }}>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
