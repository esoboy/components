@blaze

@use('Esoboy\Ui\Support\RichText')

{{-- A control's dropdown: a choice of values (paragraph style, font, size, alignment, line height, media size), its button showing the current one, or the table's row and column commands. --}}
@props([
    'name',
])

@php
$fonts = [
    'sans-serif' => __('ui::editor.font-sans-serif'),
    'serif' => __('ui::editor.font-serif'),
    'monospace' => __('ui::editor.font-monospace'),
    'arial' => __('ui::editor.font-arial'),
    'georgia' => __('ui::editor.font-georgia'),
    'times-new-roman' => __('ui::editor.font-times-new-roman'),
    'courier-new' => __('ui::editor.font-courier-new'),
    'microsoft-yahei' => __('ui::editor.font-microsoft-yahei'),
    'simsun' => __('ui::editor.font-simsun'),
    'simhei' => __('ui::editor.font-simhei'),
    'kaiti' => __('ui::editor.font-kaiti'),
    'fangsong' => __('ui::editor.font-fangsong'),
];
$headings = [1 => __('ui::editor.heading-1'), 2 => __('ui::editor.heading-2'), 3 => __('ui::editor.heading-3'), 4 => __('ui::editor.heading-4'), 5 => __('ui::editor.heading-5'), 6 => __('ui::editor.heading-6')];

// [label, trigger: icons or the current label, items: [command, value, label, icon, style], radio]
[$label, $trigger, $items, $radio] = match ($name) {
    'heading' => [__('ui::editor.paragraph-style'), 'icons', [
        ['paragraph', '', __('ui::editor.paragraph'), 'pilcrow', null],
        ...array_map(fn (int $level) => ['heading', (string) $level, $headings[$level], "heading-{$level}", null], RichText::HEADINGS),
    ], true],
    'font-family' => [__('ui::editor.font-family'), 'label', [
        ['font-family', '', __('ui::editor.font-default'), null, null],
        ...array_map(fn (string $key, string $font) => ['font-family', $font, $fonts[$key], null, "font-family: {$font}"], array_keys(RichText::FONTS), RichText::FONTS),
    ], true],
    'font-size' => [__('ui::editor.font-size'), 'label', [
        ['font-size', '', __('ui::editor.size-default'), null, null],
        ...array_map(fn (int $size) => ['font-size', "{$size}px", (string) $size, null, null], RichText::SIZES),
    ], true],
    'text-align' => [__('ui::editor.text-align'), 'icons', [
        ['text-align', 'left', __('ui::editor.align-left'), 'text-align-start', null],
        ['text-align', 'center', __('ui::editor.align-center'), 'text-align-center', null],
        ['text-align', 'right', __('ui::editor.align-right'), 'text-align-end', null],
        ['text-align', 'justify', __('ui::editor.align-justify'), 'text-align-justify', null],
    ], true],
    'line-height' => [__('ui::editor.line-height'), 'line-height', [
        ['line-height', '', __('ui::editor.line-height-default'), null, null],
        ...array_map(fn (string $height) => ['line-height', $height, $height, null, null], RichText::LINE_HEIGHTS),
    ], true],
    'media-size' => [__('ui::editor.media-size'), 'label', [
        ['media-size', '', __('ui::editor.size-original'), null, null],
        ['media-size', '25%', __('ui::editor.size-small'), null, null],
        ['media-size', '50%', __('ui::editor.size-medium'), null, null],
        ['media-size', '75%', __('ui::editor.size-large'), null, null],
        ['media-size', '100%', __('ui::editor.size-full'), null, null],
    ], true],
    'table-rows' => [__('ui::editor.rows'), 'rows-3', [
        ['add-row-before', '', __('ui::editor.add-row-before'), 'between-horizontal-start', null],
        ['add-row-after', '', __('ui::editor.add-row-after'), 'between-horizontal-end', null],
        ['header-row', '', __('ui::editor.header-row'), 'panel-top', null],
        ['delete-row', '', __('ui::editor.delete-row'), 'trash', null],
    ], false],
    'table-columns' => [__('ui::editor.columns'), 'columns-3', [
        ['add-column-before', '', __('ui::editor.add-column-before'), 'between-vertical-start', null],
        ['add-column-after', '', __('ui::editor.add-column-after'), 'between-vertical-end', null],
        ['header-column', '', __('ui::editor.header-column'), 'panel-left', null],
        ['delete-column', '', __('ui::editor.delete-column'), 'trash', null],
    ], false],
    default => throw new InvalidArgumentException("[{$name}] is not an Editor menu; use heading, font-family, font-size, text-align, line-height, media-size, table-rows or table-columns."),
};
@endphp

<x-ui::dropdown-menu>
    <x-ui::dropdown-menu.trigger variant="ghost" :size="$trigger === 'label' ? 'sm' : 'icon-sm'" data-editor-menu="{{ $name }}" :aria-label="$label" :attributes="$attributes">
        @if ($trigger === 'icons')
        @foreach ($items as [, $value, , $icon])
        <x-ui::icon :name="$icon" data-editor-icon="{{ $value }}" :hidden="! $loop->first" />
        @endforeach
        @elseif ($trigger === 'label')
        <span data-editor-current data-editor-default="{{ $items[0][2] }}">{{ $items[0][2] }}</span>
        @else
        <x-ui::icon :name="$trigger" />
        @endif
        <x-ui::icon name="chevron-down" data-editor-chevron />
    </x-ui::dropdown-menu.trigger>
    <x-ui::dropdown-menu.content data-editor-menu-content="{{ $name }}">
        @foreach ($items as [$command, $value, $itemLabel, $icon, $style])
        <x-ui::dropdown-menu.item :role="$radio ? 'menuitemradio' : 'menuitem'" :aria-checked="$radio ? 'false' : false" data-editor-command="{{ $command }}" data-editor-value="{{ $value }}" data-editor-label="{{ $itemLabel }}" :variant="str_starts_with($command, 'delete') ? 'destructive' : 'default'">
            @if ($icon)
            <x-ui::icon :name="$icon" />
            @endif
            <span @if ($style) style="{{ $style }}" @endif>{{ $itemLabel }}</span>
            @if ($radio)
            <span data-slot="dropdown-menu-item-indicator"><x-ui::icon name="check" /></span>
            @endif
        </x-ui::dropdown-menu.item>
        @endforeach
    </x-ui::dropdown-menu.content>
</x-ui::dropdown-menu>
