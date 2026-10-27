@blaze

@use('Esoboy\Ui\Support\Sequence')

@aware([
    'current' => 1,
])

{{-- status overrides the position: complete, current, upcoming or error. --}}
@props([
    'title' => null,
    'description' => null,
    'status' => null,
])

@php
$step = Sequence::next('steps');
$status ??= $step < (int) $current ? 'complete' : ($step === (int) $current ? 'current' : 'upcoming');
@endphp

<li {{ $attributes->merge(['data-slot' => 'steps-item', 'data-status' => $status, 'aria-current' => $status === 'current' ? 'step' : false]) }}>
    <span data-slot="steps-indicator">
        @if ($status === 'complete')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        @elseif ($status === 'error')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        @else
        {{ $step }}
        @endif
    </span>
    <span data-slot="steps-content">
        @if ($title)<span data-slot="steps-title">{{ $title }}</span>@endif
        @if ($description)<span data-slot="steps-description">{{ $description }}</span>@endif
        {{ $slot }}
    </span>
</li>
