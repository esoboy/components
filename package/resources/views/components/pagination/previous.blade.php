@blaze

@props([
    'text' => null,
])

<x-ui::pagination.link size="default" :attributes="$attributes->merge(['aria-label' => __('ui::pagination.previous_page')])">
    <svg data-icon="inline-start" class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    <span class="hidden sm:block">{{ $text ?? __('ui::pagination.previous') }}</span>
</x-ui::pagination.link>
