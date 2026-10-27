@blaze

@use('Esoboy\Ui\Support\Overlay')

@php
$id = Overlay::open('combobox');
@endphp

{{-- ui.js renders a chip per chosen item from the template, in front of the input. --}}
<div {{ $attributes->merge(['data-slot' => 'combobox-chips', 'data-combobox-anchor' => true, 'style' => 'anchor-name: --'.$id]) }}>
    <template data-combobox-chip>
        <div data-slot="combobox-chip">
            <span data-combobox-chip-label></span>
            <x-ui::button variant="ghost" size="icon-xs" data-combobox-chip-remove :aria-label="__('ui::common.remove')">
                <x-ui::icon name="x" />
            </x-ui::button>
        </div>
    </template>
    {{ $slot }}
</div>
