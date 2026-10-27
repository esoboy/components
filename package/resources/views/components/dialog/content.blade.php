@blaze

@aware([
    'name',
])

@props([
    'showCloseButton' => true,
])

{{-- wire:ignore.self keeps Livewire morphs from stripping the open state. --}}
<dialog {{ $attributes->merge([
    'id' => $name,
    'data-slot' => 'dialog-content',
    'aria-labelledby' => $name.'-title',
    'aria-describedby' => $name.'-description',
]) }} wire:ignore.self>
    {{ $slot }}
    @if ($showCloseButton)
        <x-ui::button variant="ghost" size="icon-sm" :commandfor="$name" command="close">
            <x-ui::icon name="x" />
            <span class="sr-only">{{ __('ui::common.close') }}</span>
        </x-ui::button>
    @endif
</dialog>
