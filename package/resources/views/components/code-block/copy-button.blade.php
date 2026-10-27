@blaze

@props([
    'value' => null,
    'variant' => 'ghost',
    'size' => 'icon-xs',
])

{{-- Copies value, or the code of its block. The check shows for two seconds. --}}
<x-ui::button :variant="$variant" :size="$size" :attributes="$attributes->merge([
    'data-code-block-copy' => true,
    'data-value' => $value ?? false,
    'aria-label' => __('ui.code_block.copy'),
    'data-label-copy' => __('ui.code_block.copy'),
    'data-label-copied' => __('ui.code_block.copied'),
])">
    <svg data-copy-icon="copy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
    <svg data-copy-icon="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
</x-ui::button>
<span aria-live="polite" class="sr-only" data-code-block-status></span>
