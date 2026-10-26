@blaze

@aware([
    'defaultOpen' => false,
])

<div {{ $attributes->merge(['data-slot' => 'collapsible-content', 'data-open' => (bool) $defaultOpen, 'hidden' => ! $defaultOpen]) }}>{{ $slot }}</div>
