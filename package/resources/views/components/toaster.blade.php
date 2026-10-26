@blaze(fold: true)

{{-- Place once in the layout; $this->toast(...) and $dispatch('ui-toast', ...) show toasts here. --}}
@props([
    'position' => 'bottom-right',
])

<ol {{ $attributes->merge(['data-slot' => 'toaster', 'data-position' => $position, 'popover' => 'manual', 'aria-live' => 'polite']) }}></ol>
