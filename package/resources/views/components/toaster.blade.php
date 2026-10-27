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
]) }} wire:ignore>
    <template data-icon-template="success"><x-ui::icon name="circle-check" /></template>
    <template data-icon-template="info"><x-ui::icon name="info" /></template>
    <template data-icon-template="warning"><x-ui::icon name="triangle-alert" /></template>
    <template data-icon-template="error"><x-ui::icon name="octagon-x" /></template>
    <template data-icon-template="loading"><x-ui::icon name="loader-circle" data-spin /></template>
    <template data-icon-template="close"><x-ui::icon name="x" /></template>
</div>
