@blaze

@aware([
    'multiple' => false,
])

@props([
    'value',
])

{{-- A native radio or checkbox covering the row; the questionnaire adds the shortcut letter. --}}
<label {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'questionnaire-choice', 'data-type' => $multiple ? 'checkbox' : 'radio']) }}>
    <input {{ $attributes->except(['class', 'style'])->merge(['type' => $multiple ? 'checkbox' : 'radio', 'value' => $value, 'data-slot' => 'questionnaire-choice-input']) }}>
    <span aria-hidden="true" data-slot="questionnaire-choice-indicator">
        <span data-slot="questionnaire-choice-indicator-dot"></span>
        <svg data-slot="questionnaire-choice-indicator-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    </span>
    <span data-slot="questionnaire-choice-label">{{ $slot }}</span>
    <span data-slot="questionnaire-choice-shortcut" aria-hidden="true" hidden wire:ignore></span>
</label>
