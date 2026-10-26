@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);

\Esoboy\Ui\Support\PendingControl::put($state);
@endphp

<textarea {{ $attributes->merge(['data-slot' => 'input-group-control', ...$state->attributes()]) }}>{{ $slot }}</textarea>
