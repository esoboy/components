@blaze

@php
$id = \Esoboy\Ui\Support\Overlay::open('combobox');
@endphp

{{-- ui.js renders a chip per chosen item from the template, in front of the input. --}}
<div {{ $attributes->merge(['data-slot' => 'combobox-chips', 'data-combobox-anchor' => true, 'style' => 'anchor-name: --'.$id]) }}>
    <template data-combobox-chip>
        <div data-slot="combobox-chip">
            <span data-combobox-chip-label></span>
            <x-ui::button variant="ghost" size="icon-xs" data-combobox-chip-remove :aria-label="__('ui.remove')">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </x-ui::button>
        </div>
    </template>
    {{ $slot }}
</div>
