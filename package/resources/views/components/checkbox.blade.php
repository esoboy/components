@blaze

@props([
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors, $showError);

// A checkbox with its own value is one option of a set; the set's error belongs to <x-ui::field.error>.
$ownsError = $state->showsError() && ! $attributes->has('value');
@endphp

<input {{ $attributes->merge(['type' => 'checkbox', 'data-slot' => 'checkbox', ...$state->attributes()]) }}>
@if ($ownsError)
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
