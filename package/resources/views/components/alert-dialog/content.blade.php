@blaze

@aware([
    'name',
])

@props([
    'size' => 'default',
])

{{-- wire:ignore.self keeps Livewire morphs from stripping the open state. --}}
<dialog {{ $attributes->merge([
    'id' => $name,
    'role' => 'alertdialog',
    'data-slot' => 'alert-dialog-content',
    'data-size' => $size,
    'aria-labelledby' => $name.'-title',
    'aria-describedby' => $name.'-description',
]) }} wire:ignore.self>{{ $slot }}</dialog>
