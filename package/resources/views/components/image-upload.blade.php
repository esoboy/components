@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')
@use('Esoboy\Ui\Support\UploadedFiles')

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'src' => null,
    'accept' => 'image/*',
    'disabled' => false,
    'shape' => 'square',
    'alt' => '',
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$invalid = $state->invalid();
$model = collect($attributes->getAttributes())->first(fn ($value, $name) => str_starts_with($name, 'wire:model'));
$upload = UploadedFiles::of(Model::value($attributes))[0] ?? null;
$image = $upload['url'] ?? $src;
@endphp

{{-- One image: the new upload, else src. Click or drop to replace; the new image shows before the upload ends. --}}
<eso-file-upload {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'image-upload', 'data-shape' => $shape]) }}>
    <label data-slot="image-upload-dropzone" @if ($invalid) aria-invalid="true" @endif>
        <input {{ $attributes->except(['class', 'style'])->merge(['type' => 'file', 'data-slot' => 'file-upload-input', 'accept' => $accept, 'disabled' => (bool) $disabled, 'aria-label' => __('ui::image-upload.label'), ...$state->attributes()]) }}>
        <img data-slot="image-upload-image" src="{{ $image }}" alt="{{ $alt }}" @if (! $image) hidden @endif>
        <span data-slot="image-upload-placeholder" @if ($image) hidden @endif>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
        </span>
        <span data-slot="image-upload-overlay" aria-hidden="true">
            <svg data-overlay="change" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>
            <svg data-overlay="uploading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        </span>
    </label>
    @if ($upload && $model && ! $disabled)
    <x-ui::button variant="secondary" size="icon-xs" data-image-upload-remove :aria-label="__('ui::common.remove')" wire:click="$removeUpload('{{ $model }}', '{{ $upload['filename'] }}')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </x-ui::button>
    @endif
    <x-ui::field.error :state="$state" />
</eso-file-upload>
