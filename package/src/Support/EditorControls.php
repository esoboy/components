<?php

namespace Esoboy\Ui\Support;

/**
 * The Editor's controls: the toolbar's groups, and what each needs of the format or the
 * tools to show (a control the Editor can't use renders nothing, so one toolbar fits
 * every format).
 */
final class EditorControls
{
    public const TOOLBAR = [
        ['undo', 'redo'],
        ['heading', 'font-family', 'font-size'],
        ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'color', 'clear-formatting'],
        ['link'],
        ['text-align', 'line-height', 'outdent', 'indent', 'text-indent'],
        ['bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'horizontal-rule'],
        ['media', 'embed', 'table', 'details', 'math', 'emoji'],
        ['find', 'source', 'fullscreen'],
    ];

    /**
     * What /, typed in the text, lists: blocks and media.
     */
    public const SLASH = ['paragraph', 'heading-1', 'heading-2', 'heading-3', 'heading-4', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'horizontal-rule', 'media', 'embed', 'table', 'details', 'math'];

    /** The formatting that clearing takes off. */
    private const FORMATTING = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height'];

    /**
     * @param  list<string>  $tools
     */
    public static function allowed(string $control, RichTextFormat $format, array $tools): bool
    {
        return match ($control) {
            'undo', 'redo' => true,
            'paragraph' => $format->has('heading'),
            'heading-1', 'heading-2', 'heading-3', 'heading-4' => $format->has('heading'),
            'color' => $format->has('color', 'highlight'),
            'clear-formatting' => $format->has(...self::FORMATTING),
            'indent', 'outdent' => $format->has('indent', 'bullet-list', 'ordered-list', 'task-list'),
            'media' => $format->has('image', 'audio', 'video', 'file'),
            'find' => in_array('find-replace', $tools, true),
            'source', 'fullscreen' => in_array($control, $tools, true),
            default => $format->has($control),
        };
    }

    /**
     * The toolbar's groups with only the controls the Editor can use, empty ones gone.
     *
     * @param  list<string>  $tools
     * @return list<list<string>>
     */
    public static function toolbar(RichTextFormat $format, array $tools): array
    {
        $groups = array_map(fn (array $group) => array_values(array_filter($group, fn (string $control) => self::allowed($control, $format, $tools))), self::TOOLBAR);

        return array_values(array_filter($groups));
    }
}
