@blaze

<li {{ $attributes->merge(['data-slot' => 'breadcrumb-separator', 'role' => 'presentation', 'aria-hidden' => 'true']) }}>
    @if ($slot->isEmpty())
    <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
    @else
    {{ $slot }}
    @endif
</li>
