@blaze

@use('Esoboy\Ui\Support\RichText')

{{-- Not in shadcn: rich text an Editor wrote, shown as it was written. The HTML is sanitized to the format first, so it can come from anywhere. --}}
@props([
    'html' => null,
    'format' => 'full',
])

<div {{ $attributes->merge(['data-slot' => 'rich-text']) }}>{!! RichText::sanitize($html, $format) !!}</div>
