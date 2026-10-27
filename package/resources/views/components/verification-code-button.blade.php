@blaze

{{-- A Button that counts down once the server sends the code and calls $this->countdown($name). --}}
@props([
    'name' => 'verification-code',
    'seconds' => 60,
    'variant' => 'outline',
    'size' => 'default',
])

<eso-verification-code-button data-slot="verification-code-button" data-name="{{ $name }}" data-seconds="{{ $seconds }}" data-label-countdown="{{ __('ui.verification_code.countdown') }}" data-label-resend="{{ __('ui.verification_code.resend') }}">
    <x-ui::button :variant="$variant" :size="$size" :attributes="$attributes">
        <span data-verification-code-label>{{ $slot }}</span>
    </x-ui::button>
</eso-verification-code-button>
