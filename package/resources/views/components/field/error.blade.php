@blaze

@props([
    'name' => null,
    'messages' => null,
    'bag' => 'default',
])

@php
$messages ??= \Esoboy\Ui\Support\ControlState::messages($errors, $name, $bag);
$custom = $slot->hasActualContent();
@endphp

@if ($custom || $messages)
<div {{ $attributes->merge(['role' => 'alert', 'data-slot' => 'field-error']) }}>
    @if ($custom)
        {{ $slot }}
    @elseif (count($messages) === 1)
        {{ $messages[0] }}
    @else
        <ul>
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>
@endif
