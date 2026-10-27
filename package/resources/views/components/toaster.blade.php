@blaze

{{-- Place once in the layout; Ui::toast(...) and $dispatch('ui-toast', ...) show toasts here. --}}
@props([
    'limit' => 3,
    'timeout' => 5000,
])

<div {{ $attributes->merge([
    'role' => 'region',
    'aria-live' => 'polite',
    'aria-atomic' => 'false',
    'aria-relevant' => 'additions text',
    'aria-label' => __('ui::toast.label'),
    'tabindex' => '-1',
    'popover' => 'manual',
    'data-slot' => 'toast-viewport',
    'data-limit' => $limit,
    'data-timeout' => $timeout,
    'data-close-label' => __('ui::toast.close'),
]) }} wire:ignore></div>
