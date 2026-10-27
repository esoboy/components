@blaze

@aware([
    'required' => false,
])

{{-- Shown when an item can't move on; the text defaults to what the item needs. --}}
<p {{ $attributes->merge(['data-slot' => 'questionnaire-error', 'hidden' => true]) }}>@if ($slot->isEmpty()){{ $required ? __('ui::questionnaire.required') : __('ui::questionnaire.optional') }}@else{{ $slot }}@endif</p>
