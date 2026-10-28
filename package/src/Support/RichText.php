<?php

namespace Esoboy\Ui\Support;

use InvalidArgumentException;

/**
 * Rich text formats: what an Editor lets people write, and what sanitize() keeps.
 *
 * A format is a preset (text, basic, full), a name registered with format(), or a list
 * of features and presets. Content is plain HTML: its elements, and inline style for the
 * rest (colors, sizes, alignment, …), so it looks the same wherever it is pasted. Each
 * style takes only the fixed values the Editor offers, declaration by declaration.
 */
final class RichText
{
    /**
     * Each preset's features; a preset named in a list brings its own.
     */
    public const PRESETS = [
        'text' => [],
        'basic' => ['heading', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'horizontal-rule', 'emoji'],
        'full' => ['basic', 'code-block', 'image', 'audio', 'video', 'file', 'embed', 'table', 'details', 'math'],
    ];

    /**
     * Every feature; paragraphs and line breaks come with all of them.
     */
    public const FEATURES = ['heading', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'horizontal-rule', 'emoji', 'code-block', 'image', 'audio', 'video', 'file', 'embed', 'table', 'details', 'math'];

    /**
     * What an Editor can offer besides its formats.
     */
    public const TOOLS = ['toolbar', 'bubble-menu', 'slash-menu', 'drag-handle', 'find-replace', 'source', 'fullscreen', 'character-count', 'typography', 'markdown'];

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
     * Highlights: light backgrounds, always under dark text.
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

    /** @var array<string, list<string>> */
    private static array $formats = [];

    /** @var array<string, array{string, string}> */
    private static array $platforms = [];

    /** @var array<string, RichTextFormat> */
    private static array $resolved = [];

    /**
     * Names a format, for format="…" and #[Sanitize('…')].
     *
     * @param  list<string>  $features  features and presets
     */
    public static function format(string $name, array $features): void
    {
        if (isset(self::PRESETS[$name])) {
            throw new InvalidArgumentException("[{$name}] is a preset; name the format something else.");
        }

        self::$formats[$name] = $features;
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
        $features = self::features($format ?? 'full');

        return self::$resolved[implode(',', $features)] ??= new RichTextFormat($features);
    }

    /**
     * Cleans HTML to what the format writes.
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
     * The text of rich text: a line per block, a tab between table cells, formulas as
     * their LaTeX.
     */
    public static function plainText(?string $html): string
    {
        $html = self::sanitize($html);

        $html = preg_replace_callback('~<math\b.*?</math>~is', fn (array $math) => preg_match('~<annotation encoding="application/x-tex">(.*?)</annotation>~is', $math[0], $tex) ? $tex[1] : '', $html) ?? '';
        $html = preg_replace(['~</p>\s*(?=</t[dh]>)~i', '~<br\s*/?>~i', '~</(p|h[1-6]|li|blockquote|pre|div|tr|summary)>~i', '~</t[dh]>~i'], ['', "\n", "\n", "\t"], $html) ?? '';

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace(["~[ \t]+\n~", "~\n{3,}~"], ["\n", "\n\n"], $text) ?? '');
    }

    /**
     * @param  string|list<string>  $format
     * @return list<string>
     */
    private static function features(string|array $format, int $depth = 0): array
    {
        if ($depth > 8) {
            throw new InvalidArgumentException('Rich text formats name each other in a loop.');
        }

        if (is_string($format)) {
            $format = self::PRESETS[$format] ?? self::$formats[$format] ?? throw new InvalidArgumentException("[{$format}] is not a rich text format; use text, basic, full, a list of features or a name given to RichText::format().");
        }

        $features = [];

        foreach ($format as $name) {
            if (in_array($name, self::FEATURES, true)) {
                $features[] = $name;
            } elseif (isset(self::PRESETS[$name]) || isset(self::$formats[$name])) {
                array_push($features, ...self::features($name, $depth + 1));
            } else {
                throw new InvalidArgumentException("[{$name}] is not a rich text feature; use one of: ".implode(', ', self::FEATURES).'.');
            }
        }

        // In FEATURES order, so a format's name doesn't depend on how it was listed.
        return array_values(array_intersect(self::FEATURES, $features));
    }
}
