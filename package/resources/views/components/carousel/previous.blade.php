@blaze

<x-ui::button variant="outline" size="icon-sm" :attributes="$attributes->merge(['data-carousel' => 'previous', 'disabled' => true])" wire:ignore.self>
    <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    <span class="sr-only">{{ __('ui.carousel.previous') }}</span>
</x-ui::button>
