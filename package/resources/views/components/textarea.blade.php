@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
@endphp

<textarea {{ $attributes->merge(['data-slot' => 'textarea', ...$state->attributes()]) }}>{{ $slot }}</textarea>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
