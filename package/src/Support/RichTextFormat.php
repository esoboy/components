<?php

namespace Esoboy\Ui\Support;

use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * A resolved rich text format (RichText::resolve()): its features, the sanitizer that
 * keeps exactly what they write, and what the Editor's script needs to write it.
 */
final class RichTextFormat
{
    // The inline styles the Editor writes beside the ones people choose.
    public const CELL = 'border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top';

    public const HEADER_CELL = 'background-color:rgba(128,128,128,0.1);text-align:left';

    public const CARD = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(128,128,128,0.4);border-radius:8px;color:inherit;text-decoration:none';

    private const MATHML = ['math' => ['display'], 'semantics' => [], 'annotation' => ['encoding'], 'mrow' => [], 'mi' => ['mathvariant'], 'mn' => ['mathvariant'], 'mo' => ['stretchy', 'fence', 'separator', 'lspace', 'rspace', 'form', 'largeop', 'movablelimits', 'accent', 'minsize', 'maxsize', 'symmetric'], 'ms' => [], 'mtext' => ['mathvariant'], 'mspace' => ['width', 'height', 'depth'], 'msup' => [], 'msub' => [], 'msubsup' => [], 'mfrac' => ['linethickness'], 'msqrt' => [], 'mroot' => [], 'mstyle' => ['displaystyle', 'scriptlevel', 'mathcolor', 'mathbackground', 'mathsize'], 'munder' => ['accentunder'], 'mover' => ['accent'], 'munderover' => ['accent', 'accentunder'], 'mtable' => ['columnalign', 'rowspacing', 'columnspacing', 'rowlines', 'columnlines', 'displaystyle'], 'mtr' => [], 'mtd' => ['columnalign', 'columnspan', 'rowspan'], 'mpadded' => ['width', 'height', 'depth', 'lspace', 'voffset'], 'mphantom' => [], 'menclose' => ['notation'], 'merror' => [], 'mmultiscripts' => [], 'mprescripts' => [], 'none' => []];

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * @param  list<string>  $features
     */
    public function __construct(public readonly array $features) {}

    /**
     * Whether it has any of these features.
     */
    public function has(string ...$features): bool
    {
        return array_intersect($features, $this->features) !== [];
    }

    /**
     * The tools an Editor offers: those given, or by default all but typography (for
     * plain text, only finding, full screen and the count).
     *
     * @param  list<string>|null  $tools
     * @return list<string>
     */
    public function tools(?array $tools = null): array
    {
        if ($tools === null) {
            return $this->features === []
                ? ['toolbar', 'find-replace', 'fullscreen', 'character-count']
                : array_values(array_diff(RichText::TOOLS, ['typography']));
        }

        foreach ($tools as $tool) {
            if (! in_array($tool, RichText::TOOLS, true)) {
                throw new InvalidArgumentException("[{$tool}] is not an Editor tool; use one of: ".implode(', ', RichText::TOOLS).'.');
            }
        }

        return $tools;
    }

    /**
     * Cleans HTML to what the format writes. Images, players and embeds whose address
     * didn't pass go entirely, rather than stay as empty boxes; spans left without a
     * style go too, their text kept.
     */
    public function sanitize(string $html): string
    {
        $html = ($this->sanitizer ??= new HtmlSanitizer($this->config()))->sanitize($html);
        $html = preg_replace('~<img(?![^>]* src=)[^>]*/>|<(iframe|video|audio)(?![^>]* src=)[^>]*></\1>~', '', $html) ?? '';

        do {
            $html = preg_replace('~<span>((?:(?!<span[\s>]).)*?)</span>~s', '$1', $html, -1, $count) ?? '';
        } while ($count);

        return $html;
    }

    /**
     * What the Editor's script reads from data-format.
     *
     * @return array<string, mixed>
     */
    public function client(): array
    {
        return [
            'features' => $this->features,
            'headings' => RichText::HEADINGS,
            'colors' => array_values(RichText::COLORS),
            'highlights' => array_values(RichText::HIGHLIGHTS),
            'highlightText' => RichText::HIGHLIGHT_TEXT,
            'fonts' => array_values(RichText::FONTS),
            'sizes' => RichText::SIZES,
            'lineHeights' => RichText::LINE_HEIGHTS,
            'indents' => RichText::INDENTS,
            'platforms' => array_values(RichText::platforms()),
            'styles' => ['cell' => self::CELL, 'headerCell' => self::HEADER_CELL, 'card' => self::CARD],
        ];
    }

    /**
     * Each element's allowed declarations, property => allowed values (or a pattern).
     *
     * @return array<string, array<string, list<string>|string>>
     */
    public function styles(): array
    {
        $styles = [];
        $allow = function (array $elements, array $declarations) use (&$styles) {
            foreach ($elements as $element) {
                $styles[$element] = [...$styles[$element] ?? [], ...$declarations];
            }
        };

        $blocks = ['p', ...array_map(fn (int $level) => "h{$level}", RichText::HEADINGS)];
        $size = '/^(\d{1,4}px|([1-9]\d?|100)%)$/';
        $pixels = '/^\d{1,4}px$/';

        if ($this->has('text-align')) {
            $allow($blocks, ['text-align' => ['left', 'center', 'right', 'justify']]);
        }

        if ($this->has('indent')) {
            $allow($blocks, ['margin-left' => array_map(fn (int $level) => ($level * 2).'em', range(1, RichText::INDENTS))]);
        }

        if ($this->has('text-indent')) {
            $allow(['p'], ['text-indent' => ['2em']]);
        }

        if ($this->has('line-height')) {
            $allow($blocks, ['line-height' => RichText::LINE_HEIGHTS]);
        }

        if ($this->has('color')) {
            $allow(['span'], ['color' => array_values(RichText::COLORS)]);
        }

        if ($this->has('font-family')) {
            $allow(['span'], ['font-family' => array_values(RichText::FONTS)]);
        }

        if ($this->has('font-size')) {
            $allow(['span'], ['font-size' => array_map(fn (int $size) => "{$size}px", RichText::SIZES)]);
        }

        if ($this->has('highlight')) {
            $allow(['mark'], ['background-color' => array_values(RichText::HIGHLIGHTS), 'color' => [RichText::HIGHLIGHT_TEXT]]);
        }

        if ($this->has('task-list')) {
            $allow(['ul'], ['list-style-type' => ['none'], 'padding-left' => ['0']]);
            $allow(['li'], ['display' => ['flex'], 'align-items' => ['baseline'], 'gap' => ['0.5em']]);
        }

        if ($this->has('image', 'video', 'embed')) {
            $allow(['img', 'video', 'iframe'], ['display' => ['block'], 'width' => $size, 'max-width' => ['100%'], 'height' => ['auto'], 'margin-left' => ['auto'], 'margin-right' => ['auto']]);
            $allow(['iframe'], ['aspect-ratio' => ['16/9'], 'border' => ['0']]);
        }

        if ($this->has('audio')) {
            $allow(['audio'], ['display' => ['block'], 'width' => ['100%']]);
        }

        if ($this->has('file')) {
            $allow(['a'], self::declarations(self::CARD));
            $allow(['span'], ['opacity' => ['0.6']]);
        }

        if ($this->has('table')) {
            $allow(['div'], ['overflow-x' => ['auto']]);
            $allow(['table'], ['border-collapse' => ['collapse'], 'width' => '/^(\d{1,5}px|100%)$/', 'min-width' => $pixels]);
            $allow(['col'], ['width' => $pixels, 'min-width' => $pixels]);
            $allow(['td', 'th'], [...self::declarations(self::CELL), 'text-align' => ['left', 'center', 'right', 'justify']]);
            $allow(['th'], self::declarations(self::HEADER_CELL));
        }

        return $styles;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function declarations(string $style): array
    {
        $declarations = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = explode(':', $declaration, 2);
            $declarations[$property] = array_values(array_unique([...$declarations[$property] ?? [], $value]));
        }

        return $declarations;
    }

    private function config(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->withAttributeSanitizer(new RichTextAttributes($this->styles(), $this->hosts()));

        // Elements and their attributes, by the features that write them; anything else
        // is unwrapped, its text kept (a div, or older HTML's <b> and <i>, keep theirs).
        $elements = ['p' => ['style'], 'br' => [], 'div' => ['style']];
        $add = function (array $more) use (&$elements) {
            foreach ($more as $element => $attributes) {
                $elements[$element] = array_values(array_unique([...$elements[$element] ?? [], ...$attributes]));
            }
        };

        $features = [
            'heading' => array_fill_keys(array_map(fn (int $level) => "h{$level}", RichText::HEADINGS), ['style']),
            'bold' => ['strong' => [], 'b' => []],
            'italic' => ['em' => [], 'i' => []],
            'underline' => ['u' => []],
            'strike' => ['s' => [], 'del' => []],
            'code' => ['code' => []],
            'superscript' => ['sup' => []],
            'subscript' => ['sub' => []],
            'link' => ['a' => ['href', 'target', 'rel', 'title']],
            'color' => ['span' => ['style']],
            'font-family' => ['span' => ['style']],
            'font-size' => ['span' => ['style']],
            'highlight' => ['mark' => ['style']],
            'bullet-list' => ['ul' => [], 'li' => []],
            'ordered-list' => ['ol' => ['start'], 'li' => []],
            'task-list' => ['ul' => ['style'], 'li' => ['style'], 'input' => ['type', 'checked', 'disabled']],
            'blockquote' => ['blockquote' => []],
            'horizontal-rule' => ['hr' => []],
            'code-block' => ['pre' => [], 'code' => []],
            'image' => ['img' => ['src', 'alt', 'title', 'width', 'height', 'style']],
            'audio' => ['audio' => ['src', 'controls', 'preload', 'title', 'style']],
            'video' => ['video' => ['src', 'poster', 'controls', 'preload', 'width', 'height', 'title', 'style']],
            'file' => ['a' => ['href', 'download', 'type', 'title', 'rel', 'style'], 'span' => ['style']],
            'embed' => ['iframe' => ['src', 'title', 'allowfullscreen', 'loading', 'referrerpolicy', 'style']],
            'table' => ['table' => ['style'], 'colgroup' => [], 'col' => ['span', 'style'], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan', 'style'], 'td' => ['colspan', 'rowspan', 'style']],
            'details' => ['details' => [], 'summary' => []],
            'math' => self::MATHML,
        ];

        foreach ($this->features as $feature) {
            $add($features[$feature] ?? []);
        }

        foreach ($elements as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        if (isset($elements['a'])) {
            $config = $config->forceAttribute('a', 'rel', 'noopener noreferrer nofollow');
        }

        // A task's box, never a field of a form around the content.
        if ($this->has('task-list')) {
            $config = $config->forceAttribute('input', 'type', 'checkbox')->forceAttribute('input', 'disabled', 'disabled');
        }

        foreach (['audio', 'video'] as $media) {
            if ($this->has($media)) {
                $config = $config->forceAttribute($media, 'controls', 'controls')->forceAttribute($media, 'preload', 'metadata');
            }
        }

        if ($this->has('embed')) {
            $config = $config->forceAttribute('iframe', 'allowfullscreen', 'allowfullscreen')->forceAttribute('iframe', 'loading', 'lazy');
        }

        // These go with what is inside them. (Head elements, style and title, can't be
        // dropped inside a body: their text stays, escaped.)
        foreach (['script', 'template', 'noscript', 'iframe', 'object', 'embed', 'svg', 'math', 'textarea', 'select', 'button', 'input'] as $element) {
            if (! isset($elements[$element])) {
                $config = $config->dropElement($element);
            }
        }

        return $config;
    }

    /**
     * The hosts embeds may play from: their platforms' players.
     *
     * @return list<string>
     */
    private function hosts(): array
    {
        if (! $this->has('embed')) {
            return [];
        }

        return array_values(array_unique(array_map(fn (array $platform) => (string) parse_url($platform[1], PHP_URL_HOST), RichText::platforms())));
    }
}
