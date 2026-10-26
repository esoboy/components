@blaze

{{-- Same element as Radio Group: wire:model binds on the group. --}}
<ui-radio-group {{ $attributes->merge(['role' => 'group', 'data-slot' => 'dropdown-menu-radio-group']) }}>{{ $slot }}</ui-radio-group>
