@blaze

@aware([
    'disabled' => false,
])

<summary {{ $attributes->merge(['data-slot' => 'accordion-trigger', 'aria-disabled' => $disabled ? 'true' : false, 'tabindex' => $disabled ? '-1' : false]) }}>
    {{ $slot }}
    <svg data-slot="accordion-trigger-icon" data-state="closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    <svg data-slot="accordion-trigger-icon" data-state="open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
</summary>
