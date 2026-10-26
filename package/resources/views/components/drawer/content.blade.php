@blaze

@aware([
    'name',
    'swipeDirection' => 'down',
    'showSwipeHandle' => false,
])

{{-- A modal <dialog> that slides in from the edge it is swiped back to. wire:ignore.self keeps morphs from closing it. --}}
<dialog id="{{ $name }}" data-slot="drawer-popup" data-swipe-direction="{{ $swipeDirection }}" data-swipe-axis="{{ in_array($swipeDirection, ['left', 'right'], true) ? 'x' : 'y' }}" aria-labelledby="{{ $name }}-title" aria-describedby="{{ $name }}-description" wire:ignore.self>
    @if ($showSwipeHandle)
    <div data-slot="drawer-swipe-handle" aria-hidden="true"></div>
    @endif
    <div {{ $attributes->merge(['data-slot' => 'drawer-content']) }}>{{ $slot }}</div>
</dialog>
