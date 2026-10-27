@blaze

@props([
    'text' => null,
])

<x-ui::pagination.link size="default" :attributes="$attributes->merge(['aria-label' => __('ui::pagination.next_page')])">
    <span class="hidden sm:block">{{ $text ?? __('ui::pagination.next') }}</span>
    <svg data-icon="inline-end" class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
</x-ui::pagination.link>
