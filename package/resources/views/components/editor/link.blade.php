@blaze

{{-- The link button and its popover: an address for the selection, applied with Enter, or the link removed. --}}
<x-ui::popover>
    <x-ui::popover.trigger variant="ghost" size="icon-sm" data-editor-link aria-pressed="false" :aria-label="__('ui::editor.link')" :attributes="$attributes"><x-ui::icon name="link" /></x-ui::popover.trigger>
    <x-ui::popover.content align="start" data-editor-link-content>
        <x-ui::input type="url" data-editor-link-input :placeholder="__('ui::editor.link-placeholder')" :aria-label="__('ui::editor.link')" />
        <x-ui::button size="icon-sm" data-editor-link-apply :aria-label="__('ui::editor.link-apply')"><x-ui::icon name="corner-down-left" /></x-ui::button>
        <x-ui::button variant="ghost" size="icon-sm" data-editor-link-remove :aria-label="__('ui::editor.link-remove')"><x-ui::icon name="trash" /></x-ui::button>
    </x-ui::popover.content>
</x-ui::popover>
