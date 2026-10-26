@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'size' => 'default',
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
@endphp

{{-- As in shadcn: class and style go to the wrapper, everything else to the <select>. --}}
<div {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'native-select-wrapper', 'data-size' => $size]) }}>
    <select {{ $attributes->except(['class', 'style'])->merge(['data-slot' => 'native-select', 'data-size' => $size, ...$state->attributes()]) }}>{{ $slot }}</select>
    <svg data-slot="native-select-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
</div>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
