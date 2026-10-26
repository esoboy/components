@blaze

@props([
    'size' => 'default',
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors, $showError);
@endphp

<input {{ $attributes->merge(['type' => 'checkbox', 'role' => 'switch', 'data-slot' => 'switch', 'data-size' => $size, ...$state->attributes()]) }}>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
