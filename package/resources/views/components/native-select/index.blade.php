@blaze

@props([
    'size' => 'default',
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors, $showError);
@endphp

{{-- The wrapper draws the box and takes class/style; every other attribute belongs to the <select>. --}}
<div {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'native-select-wrapper', 'data-size' => $size]) }}>
    <select {{ $attributes->except(['class', 'style'])->merge(['data-slot' => 'native-select', ...$state->attributes()]) }}>{{ $slot }}</select>
    <svg data-slot="native-select-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
</div>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
