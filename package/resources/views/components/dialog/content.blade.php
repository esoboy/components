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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            <span class="sr-only">{{ __('ui::common.close') }}</span>
        </x-ui::button>
    @endif
</dialog>
