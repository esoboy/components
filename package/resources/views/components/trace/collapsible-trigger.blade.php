@blaze

@aware([
    'defaultOpen' => false,
])

<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'trace-collapsible-trigger', 'data-collapsible-trigger' => true, 'aria-expanded' => $defaultOpen ? 'true' : 'false']) }}>
    {{ $slot }}
    <span data-slot="trace-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></span>
</button>
