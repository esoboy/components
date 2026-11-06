@blaze

@use('Esoboy\Ui\Support\EditorUploads')
@use('Esoboy\Ui\Support\RichText')

@aware([
    'format' => 'full',
])

{{-- What the editor's script shows as needed, as templates it clones: the bubble menus (selected text, a table, a selected block, a link, a formula), the upload placeholder, the drag handle. --}}
@php
$rich = RichText::resolve($format);
$uploads = EditorUploads::enabled();
$names = [
    'gray' => __('ui::editor.color-gray'),
    'red' => __('ui::editor.color-red'),
    'orange' => __('ui::editor.color-orange'),
    'yellow' => __('ui::editor.color-yellow'),
    'green' => __('ui::editor.color-green'),
    'blue' => __('ui::editor.color-blue'),
    'purple' => __('ui::editor.color-purple'),
    'pink' => __('ui::editor.color-pink'),
];

@endphp

@if ($rich->has('bubble-menu'))
<template data-template="bubble-text">
    <div data-slot="editor-bubble" role="toolbar" aria-label="{{ __('ui::editor.formatting') }}">
        @foreach (['bold', 'italic', 'underline', 'strike', 'code', 'link', 'color'] as $control)
        <x-ui::editor.control :name="$control" />
        @endforeach
    </div>
</template>
@if ($rich->has('table'))
<template data-template="bubble-table">
    <div data-slot="editor-bubble" role="toolbar" aria-label="{{ __('ui::editor.table') }}">
        <x-ui::editor.menu name="table-rows" />
        <x-ui::editor.menu name="table-columns" />
        <x-ui::editor.separator />
        <x-ui::editor.button command="merge-cells" />
        <x-ui::editor.button command="split-cell" />
        <x-ui::editor.separator />
        <x-ui::popover>
            <x-ui::popover.trigger><x-ui::button variant="ghost" size="icon-sm" data-editor-opens="cell-background" :aria-label="__('ui::editor.cell-background')"><x-ui::icon name="paint-bucket" /></x-ui::button></x-ui::popover.trigger>
            <x-ui::popover.content align="start" data-editor-color-content>
                <div data-slot="editor-swatches" role="group" aria-label="{{ __('ui::editor.cell-background') }}">
                    <p data-slot="editor-swatches-label">{{ __('ui::editor.cell-background') }}</p>
                    <x-ui::button variant="outline" size="icon-sm" class="rounded-md font-semibold" data-editor-swatch data-editor-command="cell-background" data-editor-value="" aria-pressed="false" aria-label="{{ __('ui::editor.highlight-none') }}"><x-ui::icon name="ban" /></x-ui::button>
                    @foreach (RichText::HIGHLIGHTS as $key => $color)
                    <x-ui::button variant="outline" size="icon-sm" class="rounded-md font-semibold" data-editor-swatch data-editor-command="cell-background" data-editor-value="{{ $color }}" aria-pressed="false" aria-label="{{ $names[$key] }}"><span style="background-color: {{ $color }}"></span></x-ui::button>
                    @endforeach
                </div>
            </x-ui::popover.content>
        </x-ui::popover>
        <x-ui::editor.menu name="cell-align" />
        <x-ui::editor.separator />
        <x-ui::editor.button command="delete-table" />
    </div>
</template>
@endif
{{-- A selected block (media, a divider, a block picked by its drag handle): what fits it, and delete. With an uploader, the media can be replaced, and an image the app has no copy of (from another site, pasted as data) saved to it. --}}
<template data-template="bubble-node">
    <div data-slot="editor-bubble" role="toolbar" aria-label="{{ __('ui::editor.selection') }}">
        @if ($rich->has('image'))
        <x-ui::input data-editor-alt-input data-editor-for="image" :placeholder="__('ui::editor.alt-placeholder')" :aria-label="__('ui::editor.alt-text')" />
        @endif
        @if ($rich->has('image', 'video', 'embed'))
        <div data-slot="editor-bubble-group" data-editor-for="image video embed">
            <x-ui::editor.menu name="media-size" />
            <x-ui::button variant="ghost" size="icon-sm" data-editor-command="media-align" data-editor-value="left" aria-pressed="false" :aria-label="__('ui::editor.align-left')"><x-ui::icon name="text-align-start" /></x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-editor-command="media-align" data-editor-value="center" aria-pressed="false" :aria-label="__('ui::editor.align-center')"><x-ui::icon name="text-align-center" /></x-ui::button>
            <x-ui::button variant="ghost" size="icon-sm" data-editor-command="media-align" data-editor-value="right" aria-pressed="false" :aria-label="__('ui::editor.align-right')"><x-ui::icon name="text-align-end" /></x-ui::button>
        </div>
        @endif
        @if ($rich->has('embed'))
        <x-ui::editor.menu name="embed-ratio" data-editor-for="embed" />
        @endif
        @if ($rich->has('image'))
        <div data-slot="editor-bubble-group" data-editor-for="image">
            <x-ui::editor.button command="caption" />
            <x-ui::editor.button command="image-row" />
            <x-ui::popover>
                <x-ui::popover.trigger><x-ui::button variant="ghost" size="icon-sm" data-editor-opens="image-link" :aria-label="__('ui::editor.image-link')"><x-ui::icon name="link" /></x-ui::button></x-ui::popover.trigger>
                <x-ui::popover.content align="start" data-editor-field-content>
                    <x-ui::input type="url" data-editor-image-link-input :placeholder="__('ui::editor.link-placeholder')" :aria-label="__('ui::editor.image-link')" />
                    <x-ui::button size="icon-sm" data-editor-action="image-link-apply" :aria-label="__('ui::editor.link-apply')"><x-ui::icon name="corner-down-left" /></x-ui::button>
                    <x-ui::button variant="ghost" size="icon-sm" data-editor-action="image-link-remove" :aria-label="__('ui::editor.link-remove')"><x-ui::icon name="trash" /></x-ui::button>
                </x-ui::popover.content>
            </x-ui::popover>
            @if ($uploads)
            <x-ui::editor.button command="save-image" hidden />
            @endif
        </div>
        @endif
        @if ($uploads && $rich->has('image', 'audio', 'video', 'file'))
        <x-ui::button variant="ghost" size="icon-sm" data-editor-action="media" data-editor-for="image video audio file" :aria-label="__('ui::editor.replace-media')"><x-ui::icon name="replace" /></x-ui::button>
        @endif
        <x-ui::editor.button command="delete-node" />
        @if ($uploads && $rich->has('image'))
        <p data-slot="editor-field-error" data-editor-image-error role="alert" hidden>{{ __('ui::editor.upload-failed') }}</p>
        @endif
    </div>
</template>
@if ($rich->has('link'))
{{-- The cursor in a link: where it goes (opening there), and changing or taking it off. --}}
<template data-template="bubble-link">
    <div data-slot="editor-bubble" role="toolbar" aria-label="{{ __('ui::editor.link') }}">
        <a data-slot="editor-link-href" data-editor-link-href target="_blank" rel="noopener noreferrer">{{ __('ui::editor.open-link') }}</a>
        <x-ui::editor.separator />
        <x-ui::editor.link />
        <x-ui::button variant="ghost" size="icon-sm" data-editor-action="link-remove" :aria-label="__('ui::editor.link-remove')"><x-ui::icon name="unlink" /></x-ui::button>
    </div>
</template>
@endif
@if ($rich->has('math'))
<template data-template="bubble-math">
    <div data-slot="editor-bubble" data-editor-bubble-form>
        <x-ui::editor.math-form />
    </div>
</template>
@endif
@endif

@if ($uploads && $rich->has('image', 'audio', 'video', 'file'))
{{-- Where dropped or pasted files will go while they upload. --}}
<template data-template="uploading">
    <span data-slot="editor-uploading" contenteditable="false"><x-ui::spinner /><span>{{ __('ui::editor.uploading') }}</span><span data-editor-upload-progress role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="{{ __('ui::editor.uploading') }}"></span><button type="button" data-editor-action="dismiss-upload" aria-label="{{ __('ui::editor.dismiss') }}"><x-ui::icon name="x" /></button></span>
</template>
{{-- Where files that wouldn't upload were to go: why (the uploader's reason), to dismiss. --}}
<template data-template="upload-failed">
    <span data-slot="editor-uploading" data-editor-failed contenteditable="false" role="alert"><x-ui::icon name="circle-alert" /><span data-editor-upload-message>{{ __('ui::editor.upload-failed') }}</span><button type="button" data-editor-action="dismiss-upload" aria-label="{{ __('ui::editor.dismiss') }}"><x-ui::icon name="x" /></button></span>
</template>
@endif

@if ($rich->has('image'))
{{-- Where a pasted picture couldn't be read (Word's chart, WPS's picture): upload it there (with an uploader), or dismiss. --}}
<template data-template="missing-image">
    <span data-slot="editor-uploading" data-editor-missing contenteditable="false" role="group" aria-label="{{ __('ui::editor.image-missing') }}"><x-ui::icon name="image-off" /><span>{{ __('ui::editor.image-missing') }}</span><span data-editor-upload-message role="alert" hidden>{{ __('ui::editor.upload-failed') }}</span>@if ($uploads)<button type="button" data-editor-action="upload-missing">{{ __('ui::editor.upload') }}</button>@endif<button type="button" data-editor-action="dismiss-upload" aria-label="{{ __('ui::editor.dismiss') }}"><x-ui::icon name="x" /></button></span>
</template>
@endif

@if ($rich->has('drag-handle'))
<template data-template="drag-handle">
    <button type="button" data-slot="editor-drag-handle" draggable="true" tabindex="-1" aria-label="{{ __('ui::editor.drag-handle') }}"><x-ui::icon name="grip-vertical" /></button>
</template>
@endif
