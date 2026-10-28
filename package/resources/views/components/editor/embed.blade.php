@blaze

{{-- An embedded video: a share link (or a player's address, or its embed code) from a platform the editor knows becomes its player. --}}
<x-ui::popover>
    <x-ui::popover.trigger variant="ghost" size="icon-sm" data-editor-opens="embed" :aria-label="__('ui::editor.embed')" :attributes="$attributes"><x-ui::icon name="square-play" /></x-ui::popover.trigger>
    <x-ui::popover.content align="start" data-editor-field-content>
        <x-ui::input type="url" data-editor-embed-input :placeholder="__('ui::editor.embed-placeholder')" :aria-label="__('ui::editor.embed')" />
        <x-ui::button size="icon-sm" data-editor-action="embed-apply" :aria-label="__('ui::editor.embed-apply')"><x-ui::icon name="corner-down-left" /></x-ui::button>
        <p data-slot="editor-field-error">{{ __('ui::editor.embed-invalid') }}</p>
    </x-ui::popover.content>
</x-ui::popover>
