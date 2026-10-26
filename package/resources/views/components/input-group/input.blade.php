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

\Esoboy\Ui\Support\PendingControl::put($state);
@endphp

<input {{ $attributes->merge(['type' => $type, 'data-slot' => 'input-group-control', ...$state->attributes()]) }}>
