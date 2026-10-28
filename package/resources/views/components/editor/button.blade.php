@blaze

{{-- One command's button: its icon and label come with it; the marks and blocks show aria-pressed. --}}
@props([
    'command',
])

@php
// command => [icon, label, toggles]
$commands = [
    'undo' => ['undo-2', __('ui::editor.undo'), false],
    'redo' => ['redo-2', __('ui::editor.redo'), false],
    'heading-1' => ['heading-1', __('ui::editor.heading-1'), true],
    'heading-2' => ['heading-2', __('ui::editor.heading-2'), true],
    'heading-3' => ['heading-3', __('ui::editor.heading-3'), true],
    'bullet-list' => ['list', __('ui::editor.bullet-list'), true],
    'ordered-list' => ['list-ordered', __('ui::editor.ordered-list'), true],
    'blockquote' => ['text-quote', __('ui::editor.blockquote'), true],
    'code-block' => ['square-code', __('ui::editor.code-block'), true],
    'bold' => ['bold', __('ui::editor.bold'), true],
    'italic' => ['italic', __('ui::editor.italic'), true],
    'underline' => ['underline', __('ui::editor.underline'), true],
    'strike' => ['strikethrough', __('ui::editor.strike'), true],
    'code' => ['code', __('ui::editor.code'), true],
    'horizontal-rule' => ['minus', __('ui::editor.horizontal-rule'), false],
];
[$icon, $label, $toggles] = $commands[$command] ?? throw new InvalidArgumentException("[{$command}] is not an Editor command; use one of: ".implode(', ', array_keys($commands)).'.');
@endphp

<x-ui::button variant="ghost" size="icon-sm" :attributes="$attributes->merge(['data-editor-command' => $command, 'aria-label' => $label, 'aria-pressed' => $toggles ? 'false' : false])"><x-ui::icon :name="$icon" /></x-ui::button>
