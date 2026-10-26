@blaze

@props([
    'value',
])

{{-- A native radio inside the group; class styles the row. --}}
<label {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'dropdown-menu-radio-item']) }}>
    <input {{ $attributes->except(['class', 'style'])->merge(['type' => 'radio', 'role' => 'menuitemradio', 'value' => $value]) }}>
    <span data-slot="dropdown-menu-item-indicator">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    </span>
    {{ $slot }}
</label>
