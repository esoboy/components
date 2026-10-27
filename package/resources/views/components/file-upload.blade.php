@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'multiple' => false,
    'accept' => null,
    'disabled' => false,
    'orientation' => 'horizontal',
    'title' => null,
    'description' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
// Errors of single files (key.0, key.1...) show on those files, not on the whole upload.
$state = $state->only($state->key ? ($errors ?? null)?->get($state->key) ?? [] : []);
$invalid = $state->invalid();
$model = collect($attributes->getAttributes())->first(fn ($value, $name) => str_starts_with($name, 'wire:model'));
$files = \Esoboy\Ui\Support\UploadedFiles::of(\Esoboy\Ui\Support\Model::value($attributes));
@endphp

{{-- <eso-file-upload> adds drag and drop and shows files while they upload; the bound files then render below. --}}
<eso-file-upload {{ $attributes->only(['class', 'style'])->merge(['data-slot' => 'file-upload', 'data-orientation' => $orientation, 'data-uploading-label' => __('ui.file_upload.uploading')]) }}>
    <label data-slot="file-upload-dropzone" @if ($invalid) aria-invalid="true" @endif>
        <input {{ $attributes->except(['class', 'style'])->merge(['type' => 'file', 'data-slot' => 'file-upload-input', 'multiple' => (bool) $multiple, 'accept' => $accept ?? false, 'disabled' => (bool) $disabled, ...$state->attributes()]) }}>
        @if ($slot->isEmpty())
        <span data-slot="file-upload-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/></svg>
        </span>
        <span data-slot="file-upload-title">{{ $title ?? __('ui.file_upload.title') }}</span>
        @if ($description)
        <span data-slot="file-upload-description">{{ $description }}</span>
        @endif
        @else
        {{ $slot }}
        @endif
        <span data-slot="file-upload-progress" hidden wire:ignore><span data-slot="progress-track"><span data-slot="progress-indicator"></span></span></span>
    </label>
    <div data-slot="attachment-group" data-file-upload-list @if (! $files) hidden @endif>
        {{-- Files on their way up, drawn by the script. --}}
        <div data-file-upload-pending wire:ignore></div>
        @foreach ($files as $index => $file)
        @php
        $fileErrors = $uiField && $state->key ? ($errors ?? null)?->get("{$state->key}.{$index}") ?? [] : [];
        @endphp
        <x-ui::attachment :state="$fileErrors ? 'error' : 'done'" :orientation="$orientation" wire:key="upload-{{ $file['filename'] }}">
            <x-ui::attachment.media :variant="$file['url'] ? 'image' : 'icon'">
                @if ($file['url'])
                <img src="{{ $file['url'] }}" alt="">
                @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                @endif
            </x-ui::attachment.media>
            <x-ui::attachment.content>
                <x-ui::attachment.title>{{ $file['name'] }}</x-ui::attachment.title>
                <x-ui::attachment.description>{{ $fileErrors[0] ?? $file['size'] }}</x-ui::attachment.description>
            </x-ui::attachment.content>
            @if ($model && ! $disabled)
            <x-ui::attachment.actions>
                <x-ui::attachment.action :aria-label="__('ui.remove')" wire:click="$removeUpload('{{ $model }}', '{{ $file['filename'] }}')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </x-ui::attachment.action>
            </x-ui::attachment.actions>
            @endif
        </x-ui::attachment>
        @endforeach
    </div>
    @if ($state->showsError())
    <x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
    @endif
</eso-file-upload>
