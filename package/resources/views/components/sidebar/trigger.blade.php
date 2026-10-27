@blaze

<x-ui::button variant="ghost" size="icon-sm" :attributes="$attributes->merge(['data-sidebar' => 'trigger'])">
    <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
    <span class="sr-only">{{ __('ui::sidebar.toggle') }}</span>
</x-ui::button>
