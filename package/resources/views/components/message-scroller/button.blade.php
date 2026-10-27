@blaze

@props([
    'direction' => 'end',
    'variant' => 'secondary',
    'size' => 'icon-sm',
    'behavior' => 'smooth',
])

{{-- Inert until there is content in its direction; the scroller switches data-active. --}}
<x-ui::button :variant="$variant" :size="$size" :attributes="$attributes->merge(['data-message-scroller-button' => $direction, 'data-direction' => $direction, 'data-behavior' => $behavior, 'data-active' => 'false', 'inert' => true, 'tabindex' => '-1'])" wire:ignore.self>
    @if ($slot->isEmpty())
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
    <span class="sr-only">{{ $direction === 'end' ? __('ui::message-scroller.end') : __('ui::message-scroller.start') }}</span>
    @else
    {{ $slot }}
    @endif
</x-ui::button>
