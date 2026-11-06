<?php

namespace Esoboy\Ui\Support;

/**
 * The Editor's controls: the toolbar's groups, and what each needs of the format to show
 * (a control the Editor can't use renders nothing, so one toolbar fits every format).
 */
final class EditorControls
{
    /**
     * The toolbar's groups, each control with how much it is used: 1 always in sight,
     * 2 and then 3 going into More first when the toolbar runs out of room.
     */
    public const TOOLBAR = [
        ['undo' => 1, 'redo' => 1],
        ['heading' => 1, 'font-family' => 3, 'font-size' => 2],
        ['bold' => 1, 'italic' => 1, 'underline' => 2, 'strike' => 2, 'color' => 2, 'code' => 3, 'superscript' => 3, 'subscript' => 3, 'clear-formatting' => 3, 'format-painter' => 3],
        ['link' => 1],
        ['text-align' => 2, 'line-height' => 3, 'spacing' => 3, 'outdent' => 3, 'indent' => 3, 'text-indent' => 3],
        ['bullet-list' => 2, 'ordered-list' => 2, 'task-list' => 3, 'blockquote' => 2, 'callout' => 3, 'code-block' => 3, 'horizontal-rule' => 3],
        ['image' => 1, 'video' => 2, 'audio' => 3, 'file' => 3, 'table' => 2, 'emoji' => 2, 'math' => 3, 'details' => 3],
        ['find' => 3, 'source' => 3, 'fullscreen' => 2],
    ];

    /**
     * What /, typed in the text, lists: blocks and media.
     */
    public const SLASH = ['paragraph', 'heading-1', 'heading-2', 'heading-3', 'heading-4', 'heading-5', 'heading-6', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'callout', 'code-block', 'horizontal-rule', 'image', 'video', 'audio', 'file', 'table', 'details', 'math'];

    /** The formatting that clearing takes off. */
    private const FORMATTING = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'spacing'];

    public static function allowed(string $control, RichTextFormat $format): bool
    {
        return match ($control) {
            'undo', 'redo' => true,
            'paragraph' => $format->has('heading'),
            'heading-1', 'heading-2', 'heading-3', 'heading-4', 'heading-5', 'heading-6' => $format->has('heading'),
            'color' => $format->has('color', 'highlight'),
            'clear-formatting' => $format->has(...self::FORMATTING),
            'indent', 'outdent' => $format->has('indent', 'bullet-list', 'ordered-list', 'task-list'),
            // Uploaded with the app's uploader; a video from a link (a file's address, a
            // platform's) needs none.
            'image', 'audio', 'file' => $format->has($control) && EditorUploads::enabled(),
            'video' => $format->has('video', 'embed'),
            'find' => $format->has('find-replace'),
            'format-painter' => $format->has('format-painter') && $format->has(...self::FORMATTING),
            default => $format->has($control),
        };
    }

    /**
     * How much a control is used (TOOLBAR), 2 for one it doesn't list.
     */
    public static function priority(string $control): int
    {
        foreach (self::TOOLBAR as $group) {
            if (isset($group[$control])) {
                return $group[$control];
            }
        }

        return 2;
    }

    /**
     * The toolbar's groups with only the controls the Editor can use, empty ones gone.
     *
     * @return list<array<string, int>>
     */
    public static function toolbar(RichTextFormat $format): array
    {
        $groups = array_map(fn (array $group) => array_filter($group, fn (string $control) => self::allowed($control, $format), ARRAY_FILTER_USE_KEY), self::TOOLBAR);

        return array_values(array_filter($groups));
    }

    /** @return array<string, array{string, string, array<string, string>}> */
    public static function slash(RichTextFormat $format): array
    {
        if (! $format->has('slash-menu')) {
            return [];
        }

        $toolbar = $format->has('toolbar');
        $uploads = EditorUploads::enabled();
        // / items: control => [icon, label, attributes]
        $slash = [
            'paragraph' => ['pilcrow', __('ui::editor.paragraph'), ['data-editor-command' => 'paragraph']],
            'heading-1' => ['heading-1', __('ui::editor.heading-1'), ['data-editor-command' => 'heading', 'data-editor-value' => '1']],
            'heading-2' => ['heading-2', __('ui::editor.heading-2'), ['data-editor-command' => 'heading', 'data-editor-value' => '2']],
            'heading-3' => ['heading-3', __('ui::editor.heading-3'), ['data-editor-command' => 'heading', 'data-editor-value' => '3']],
            'heading-4' => ['heading-4', __('ui::editor.heading-4'), ['data-editor-command' => 'heading', 'data-editor-value' => '4']],
            'heading-5' => ['heading-5', __('ui::editor.heading-5'), ['data-editor-command' => 'heading', 'data-editor-value' => '5']],
            'heading-6' => ['heading-6', __('ui::editor.heading-6'), ['data-editor-command' => 'heading', 'data-editor-value' => '6']],
            'bullet-list' => ['list', __('ui::editor.bullet-list'), ['data-editor-command' => 'bullet-list']],
            'ordered-list' => ['list-ordered', __('ui::editor.ordered-list'), ['data-editor-command' => 'ordered-list']],
            'task-list' => ['list-todo', __('ui::editor.task-list'), ['data-editor-command' => 'task-list']],
            'blockquote' => ['text-quote', __('ui::editor.blockquote'), ['data-editor-command' => 'blockquote']],
            'callout' => ['message-square-warning', __('ui::editor.callout'), ['data-editor-command' => 'callout', 'data-editor-value' => RichText::CALLOUTS['info']]],
            'code-block' => ['square-code', __('ui::editor.code-block'), ['data-editor-command' => 'code-block']],
            'horizontal-rule' => ['minus', __('ui::editor.horizontal-rule'), ['data-editor-command' => 'horizontal-rule']],
            'image' => ['image', __('ui::editor.image'), ['data-editor-action' => 'media', 'data-editor-value' => 'image']],
            // The toolbar's popover, for a link too; without it, an upload.
            'video' => ['video', __('ui::editor.video'), $toolbar ? ['data-editor-action' => 'open', 'data-editor-value' => 'video'] : ['data-editor-action' => 'media', 'data-editor-value' => 'video']],
            'audio' => ['eighth-notes', __('ui::editor.audio'), ['data-editor-action' => 'media', 'data-editor-value' => 'audio']],
            'file' => ['paperclip', __('ui::editor.file'), ['data-editor-action' => 'media', 'data-editor-value' => 'file']],
            'table' => ['table', __('ui::editor.table'), ['data-editor-command' => 'table']],
            'details' => ['list-chevrons-down-up', __('ui::editor.details'), ['data-editor-command' => 'details']],
            'math' => ['radical', __('ui::editor.math'), ['data-editor-action' => 'open', 'data-editor-value' => 'math']],
        ];

        // Formulas open the toolbar's popover; a video does without it when it can be uploaded.
        return array_filter($slash, fn (string $control) => EditorControls::allowed($control, $format) && match ($control) {
            'math' => $toolbar,
            'video' => $toolbar || ($format->has('video') && $uploads),
            default => true,
        }, ARRAY_FILTER_USE_KEY);
    }
}
