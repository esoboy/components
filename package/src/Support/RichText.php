<?php

namespace Esoboy\Ui\Support;

use InvalidArgumentException;

/**
 * Rich text formats: what an Editor lets people write, and what sanitize() keeps.
 *
 * A format is a preset (text, basic, full), a name registered with format(), or a list
 * of features and presets. Its features make a whitelist, as UEditor's: the tags kept,
 * each with the attributes kept on it, and the CSS properties kept in their style. The
 * rest goes: other tags are unwrapped (their text stays), other attributes and CSS
 * properties dropped. Content is plain HTML with inline style, so it looks the same
 * wherever it is pasted.
 */
final class RichText
{
    /**
     * Each preset's features; a preset named in a list brings its own.
     */
    public const PRESETS = [
        'text' => [],
        'basic' => ['heading', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'spacing', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'horizontal-rule', 'emoji'],
        'full' => ['basic', 'code-block', 'image', 'audio', 'video', 'file', 'embed', 'table', 'details', 'math', 'callout', 'layout'],
    ];

    /**
     * Every feature; paragraphs and line breaks come with all of them. layout keeps the
     * tags no feature writes (section, div, span, small, …) and style on every tag.
     */
    public const FEATURES = ['heading', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'spacing', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'horizontal-rule', 'emoji', 'code-block', 'image', 'audio', 'video', 'file', 'embed', 'table', 'details', 'math', 'callout', 'layout'];

    /**
     * What an Editor can offer besides its formats.
     */
    public const TOOLS = ['toolbar', 'bubble-menu', 'slash-menu', 'drag-handle', 'find-replace', 'source', 'fullscreen', 'character-count', 'format-painter', 'typography', 'markdown'];

    public const HEADINGS = [1, 2, 3, 4, 5, 6];

    /**
     * Text colors: mid tones that read on light and dark pages alike.
     */
    public const COLORS = [
        'gray' => '#6b7280',
        'red' => '#dc2626',
        'orange' => '#ea580c',
        'yellow' => '#ca8a04',
        'green' => '#16a34a',
        'blue' => '#3b82f6',
        'purple' => '#a855f7',
        'pink' => '#db2777',
    ];

    /**
     * Highlights (and table cell backgrounds): light backgrounds, always under dark text.
     */
    public const HIGHLIGHTS = [
        'yellow' => '#fef08a',
        'green' => '#bbf7d0',
        'blue' => '#bfdbfe',
        'purple' => '#e9d5ff',
        'pink' => '#fbcfe8',
        'red' => '#fecaca',
        'orange' => '#fed7aa',
        'gray' => '#e5e7eb',
    ];

    public const HIGHLIGHT_TEXT = '#171717';

    /**
     * Font stacks that fall back to what each system has.
     */
    public const FONTS = [
        'sans-serif' => 'sans-serif',
        'serif' => 'serif',
        'monospace' => 'monospace',
        'arial' => 'Arial, sans-serif',
        'georgia' => 'Georgia, serif',
        'times-new-roman' => "'Times New Roman', serif",
        'courier-new' => "'Courier New', monospace",
        'microsoft-yahei' => "'Microsoft YaHei', 'PingFang SC', sans-serif",
        'simsun' => "SimSun, 'Songti SC', serif",
        'simhei' => "SimHei, 'Heiti SC', sans-serif",
        'kaiti' => "KaiTi, 'Kaiti SC', serif",
        'fangsong' => 'FangSong, STFangsong, serif',
    ];

    /** Font sizes, in px. */
    public const SIZES = [12, 13, 14, 15, 16, 18, 20, 24, 28, 32, 36, 48];

    public const LINE_HEIGHTS = ['1', '1.15', '1.5', '1.75', '2', '2.5', '3'];

    /** Indent levels, each 2em. */
    public const INDENTS = 8;

    /** Space before and after a paragraph. */
    public const SPACINGS = ['0', '5px', '10px', '15px', '20px', '30px'];

    public const LETTER_SPACINGS = ['0', '0.5px', '1px', '1.5px', '2px', '3px'];

    /**
     * Callouts: a tinted background (see-through, so it reads on light and dark pages)
     * and a bar of the palette's color.
     */
    public const CALLOUTS = [
        'info' => 'background-color:rgba(59,130,246,0.1);border-left:4px solid #3b82f6',
        'success' => 'background-color:rgba(22,163,74,0.1);border-left:4px solid #16a34a',
        'warning' => 'background-color:rgba(202,138,4,0.12);border-left:4px solid #ca8a04',
        'danger' => 'background-color:rgba(220,38,38,0.1);border-left:4px solid #dc2626',
    ];

    /**
     * Video platforms an embed may play from: a share link's pattern (a JavaScript
     * regular expression) and the player address it becomes, $1 its first capture.
     */
    public const PLATFORMS = [
        'youtube' => ['^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})', 'https://www.youtube-nocookie.com/embed/$1'],
        'bilibili' => ['^https?://(?:www\.|m\.)?bilibili\.com/video/(BV\w{10})', 'https://player.bilibili.com/player.html?bvid=$1&autoplay=0'],
        'vimeo' => ['^https?://(?:www\.)?vimeo\.com/(\d+)', 'https://player.vimeo.com/video/$1'],
        'tencent' => ['^https?://v\.qq\.com/x/(?:cover/\w+/|page/)(\w+)\.html', 'https://v.qq.com/txp/iframe/player.html?vid=$1'],
        'youku' => ['^https?://v\.youku\.com/v_show/id_([\w=]+)\.html', 'https://player.youku.com/embed/$1'],
    ];

    /** @var array<string, array{list<string>, array<string, list<string>>, list<string>}> */
    private static array $formats = [];

    /** @var array<string, array{string, string}> */
    private static array $platforms = [];

    /** @var array<string, RichTextFormat> */
    private static array $resolved = [];

    /**
     * Names a format, for format="…" and #[Sanitize('…')]: features and presets, and
     * more for its whitelist: tags with the attributes kept on them (['section' =>
     * ['style'], 'img' => ['data-ratio']]) and CSS properties.
     *
     * @param  list<string>  $features  features and presets
     * @param  array<string, list<string>>  $allow
     * @param  list<string>  $css
     */
    public static function format(string $name, array $features, array $allow = [], array $css = []): void
    {
        if (isset(self::PRESETS[$name])) {
            throw new InvalidArgumentException("[{$name}] is a preset; name the format something else.");
        }

        self::$formats[$name] = [$features, $allow, $css];
        self::$resolved = [];
    }

    /**
     * Lets embeds play from another platform: share links matching the pattern become
     * the player address, which sanitize() then lets through for its host.
     */
    public static function platform(string $name, string $pattern, string $src): void
    {
        self::$platforms[$name] = [$pattern, $src];
        self::$resolved = [];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function platforms(): array
    {
        return [...self::PLATFORMS, ...self::$platforms];
    }

    /**
     * @param  string|list<string>|null  $format  null for full
     */
    public static function resolve(string|array|null $format = null): RichTextFormat
    {
        [$features, $allow, $css] = self::collect($format ?? 'full');

        // In FEATURES order, so a format doesn't depend on how it was listed.
        $features = array_values(array_intersect(self::FEATURES, $features));

        return self::$resolved[(string) json_encode([$features, $allow, $css])] ??= new RichTextFormat($features, $allow, $css);
    }

    /**
     * Cleans HTML to the format's whitelist.
     *
     * @param  string|list<string>|null  $format
     */
    public static function sanitize(?string $html, string|array|null $format = null): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::resolve($format)->sanitize($html);
    }

    /**
     * The text of rich text, cleaned to the format first: a line per block, a tab
     * between table cells, formulas as their LaTeX.
     *
     * @param  string|list<string>|null  $format
     */
    public static function plainText(?string $html, string|array|null $format = null): string
    {
        $html = self::sanitize($html, $format);

        $html = preg_replace_callback('~<math\b.*?</math>~is', fn (array $math) => preg_match('~<annotation encoding="application/x-tex">(.*?)</annotation>~is', $math[0], $tex) ? $tex[1] : '', $html) ?? '';
        $html = preg_replace(['~(<t[dh]\b[^>]*>)\s*<p\b[^>]*>|</p>\s*(?=</t[dh]>)~i', '~<br\s*/?>~i', '~</?(p|h[1-6]|li|ul|ol|blockquote|pre|div|section|article|aside|header|footer|figure|figcaption|table|tr|details|summary|dl|dt|dd)\b[^>]*>~i', '~</t[dh]>~i'], ['', "\n", "\n", "\t"], $html) ?? '';

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // One line break between blocks, however many ended and began there.
        return trim(preg_replace("~[ \t]*\n(?:[ \t]*\n)*~", "\n", $text) ?? '');
    }

    /**
     * A format's features, with the tags and CSS properties named formats add.
     *
     * @param  string|list<string>  $format
     * @return array{list<string>, array<string, list<string>>, list<string>}
     */
    private static function collect(string|array $format, int $depth = 0): array
    {
        if ($depth > 8) {
            throw new InvalidArgumentException('Rich text formats name each other in a loop.');
        }

        [$names, $allow, $css] = is_array($format) ? [$format, [], []] : match (true) {
            isset(self::PRESETS[$format]) => [self::PRESETS[$format], [], []],
            isset(self::$formats[$format]) => self::$formats[$format],
            default => throw new InvalidArgumentException("[{$format}] is not a rich text format; use text, basic, full, a list of features or a name given to Ui::editorFormat()."),
        };

        $features = [];

        foreach ($names as $name) {
            if (in_array($name, self::FEATURES, true)) {
                $features[] = $name;
            } elseif (isset(self::PRESETS[$name]) || isset(self::$formats[$name])) {
                [$more, $tags, $properties] = self::collect($name, $depth + 1);
                array_push($features, ...$more);
                $allow = array_merge_recursive($tags, $allow);
                $css = [...$properties, ...$css];
            } else {
                throw new InvalidArgumentException("[{$name}] is not a rich text feature; use one of: ".implode(', ', self::FEATURES).'.');
            }
        }

        ksort($allow);
        sort($css);

        return [$features, array_map(fn (array $attributes) => array_values(array_unique($attributes)), $allow), array_values(array_unique($css))];
    }
}
