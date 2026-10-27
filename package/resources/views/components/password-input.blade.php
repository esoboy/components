@blaze

@use('Esoboy\Ui\Support\ControlState')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- shadcn's password Input Group, its eye a show/hide toggle. class styles the group, the rest goes to the input. --}}
@props([
    'disabled' => false,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
@endphp

<eso-password-input {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'password-input']) }}>
    <div role="group" data-slot="input-group">
        <input {{ $attributes->except(['class', 'style'])->merge(['type' => 'password', 'data-slot' => 'input-group-control', 'disabled' => (bool) $disabled, ...$state->attributes()]) }}>
        <div role="group" data-slot="input-group-addon" data-align="inline-end">
            <x-ui::input-group.button size="icon-xs" data-password-toggle aria-pressed="false" :aria-label="__('ui::password-input.toggle')" :disabled="$disabled">
                <svg data-password-icon="hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                <svg data-password-icon="shown" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
            </x-ui::input-group.button>
        </div>
    </div>
</eso-password-input>
<x-ui::field.error :state="$state" />
