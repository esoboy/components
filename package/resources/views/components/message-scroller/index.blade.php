@blaze

@aware([
    'autoScroll' => false,
    'defaultScrollPosition' => 'end',
    'scrollEdgeThreshold' => 8,
    'scrollPreviousItemPeek' => 64,
    'scrollMargin' => 0,
])

{{-- <ui-message-scroller> keeps the transcript's scroll position; its state lives in data-* attributes, so morphs leave it alone. --}}
<ui-message-scroller {{ $attributes->merge([
    'data-slot' => 'message-scroller',
    'data-auto-scroll' => (bool) $autoScroll,
    'data-default-scroll-position' => $defaultScrollPosition,
    'data-scroll-edge-threshold' => $scrollEdgeThreshold,
    'data-scroll-previous-item-peek' => $scrollPreviousItemPeek,
    'data-scroll-margin' => $scrollMargin,
    'data-pending-scroll' => in_array($defaultScrollPosition, ['end', 'last-anchor'], true),
]) }} wire:ignore.self>{{ $slot }}</ui-message-scroller>
