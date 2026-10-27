@blaze

{{-- The images inside open in one viewer and page through each other. --}}
<ui-image-preview {{ $attributes->merge(['data-slot' => 'image-preview']) }}>
    {{ $slot }}
    {{-- wire:ignore keeps Livewire morphs out of the open viewer. --}}
    <dialog data-slot="image-preview-content" aria-label="{{ __('ui.image_preview.label') }}" wire:ignore>
        <img data-slot="image-preview-image" alt="" draggable="false">
        <x-ui::spinner data-image-preview-spinner />
        <x-ui::button variant="outline" size="icon-sm" data-image-preview="close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            <span class="sr-only">{{ __('ui.close') }}</span>
        </x-ui::button>
        <x-ui::button variant="outline" size="icon-sm" data-image-preview="previous">
            <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            <span class="sr-only">{{ __('ui.image_preview.previous') }}</span>
        </x-ui::button>
        <x-ui::button variant="outline" size="icon-sm" data-image-preview="next">
            <svg class="rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span class="sr-only">{{ __('ui.image_preview.next') }}</span>
        </x-ui::button>
        <div role="toolbar" data-slot="image-preview-toolbar" aria-label="{{ __('ui.image_preview.label') }}">
            <span data-slot="image-preview-counter"></span>
            <x-ui::button variant="ghost" size="icon-sm" data-image-preview="zoom-out" :aria-label="__('ui.image_preview.zoom_out')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M8 11h6"/></svg>
            </x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-image-preview="zoom-in" :aria-label="__('ui.image_preview.zoom_in')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
            </x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-image-preview="rotate-left" :aria-label="__('ui.image_preview.rotate_left')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-image-preview="rotate-right" :aria-label="__('ui.image_preview.rotate_right')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
            </x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-image-preview="reset" :aria-label="__('ui.image_preview.reset')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
            </x-ui::button>
        </div>
    </dialog>
</ui-image-preview>
