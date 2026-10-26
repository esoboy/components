@blaze

@props([
    'type' => 'text',
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors, $showError);
@endphp

<input {{ $attributes->merge(['type' => $type, 'data-slot' => 'input', ...$state->attributes()]) }}>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
