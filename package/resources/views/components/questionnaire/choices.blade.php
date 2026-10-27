@blaze

@aware([
    'name',
    'multiple' => false,
])

{{-- The same elements as Radio Group / Checkbox Group: wire:model binds here. --}}
@if ($multiple)
<ui-checkbox-group {{ $attributes->merge(['role' => 'group', 'name' => $name.'[]', 'data-slot' => 'questionnaire-choices']) }}>{{ $slot }}</ui-checkbox-group>
@else
<ui-radio-group {{ $attributes->merge(['role' => 'radiogroup', 'name' => $name, 'data-slot' => 'questionnaire-choices']) }}>{{ $slot }}</ui-radio-group>
@endif
