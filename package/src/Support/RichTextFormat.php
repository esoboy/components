<?php

namespace Esoboy\Ui\Support;

use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * A resolved rich text format (RichText::resolve()): its features, its whitelist (the
 * tags its features write, with their attributes, and the CSS properties of their
 * style, plus what a named format adds), the sanitizer that keeps just that, and what
 * the Editor's script needs to write it.
 */
final class RichTextFormat
{
    // The inline styles the Editor writes for what it creates.
    public const CELL = 'border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top';

    public const HEADER_CELL = 'background-color:rgba(128,128,128,0.1);text-align:left';

    public const CARD = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(128,128,128,0.4);border-radius:8px;color:inherit;text-decoration:none';

    public const CALLOUT = 'padding:12px 16px;border-radius:6px';

    public const FIGURE = 'margin:0';

    public const CAPTION = 'margin-top:6px;text-align:center;font-size:0.875em;color:#6b7280';

    private const BLOCKS = ['p' => ['style'], 'h1' => ['style'], 'h2' => ['style'], 'h3' => ['style'], 'h4' => ['style'], 'h5' => ['style'], 'h6' => ['style']];

    private const MATHML = ['math' => ['display'], 'semantics' => [], 'annotation' => ['encoding'], 'mrow' => [], 'mi' => ['mathvariant'], 'mn' => ['mathvariant'], 'mo' => ['stretchy', 'fence', 'separator', 'lspace', 'rspace', 'form', 'largeop', 'movablelimits', 'accent', 'minsize', 'maxsize', 'symmetric'], 'ms' => [], 'mtext' => ['mathvariant'], 'mspace' => ['width', 'height', 'depth'], 'msup' => [], 'msub' => [], 'msubsup' => [], 'mfrac' => ['linethickness'], 'msqrt' => [], 'mroot' => [], 'mstyle' => ['displaystyle', 'scriptlevel', 'mathcolor', 'mathbackground', 'mathsize'], 'munder' => ['accentunder'], 'mover' => ['accent'], 'munderover' => ['accent', 'accentunder'], 'mtable' => ['columnalign', 'rowspacing', 'columnspacing', 'rowlines', 'columnlines', 'displaystyle'], 'mtr' => [], 'mtd' => ['columnalign', 'columnspan', 'rowspan'], 'mpadded' => ['width', 'height', 'depth', 'lspace', 'voffset'], 'mphantom' => [], 'menclose' => ['notation'], 'merror' => [], 'mmultiscripts' => [], 'mprescripts' => [], 'none' => []];

    /**
     * The tags each feature writes, with the attributes it writes on them.
     */
    private const TAGS = [
        'heading' => self::BLOCKS,
        'bold' => ['strong' => [], 'b' => []],
        'italic' => ['em' => [], 'i' => []],
        'underline' => ['u' => []],
        'strike' => ['s' => [], 'del' => []],
        'code' => ['code' => []],
        'superscript' => ['sup' => []],
        'subscript' => ['sub' => []],
        'link' => ['a' => ['href', 'target', 'rel', 'title']],
        'color' => ['span' => ['style']],
        'highlight' => ['mark' => ['style']],
        'font-family' => ['span' => ['style']],
        'font-size' => ['span' => ['style']],
        'text-align' => self::BLOCKS,
        'indent' => self::BLOCKS,
        'text-indent' => self::BLOCKS,
        'line-height' => self::BLOCKS,
        'spacing' => self::BLOCKS,
        'bullet-list' => ['ul' => [], 'li' => []],
        'ordered-list' => ['ol' => ['start', 'type'], 'li' => []],
        'task-list' => ['ul' => ['style'], 'li' => ['style'], 'input' => ['type', 'checked', 'disabled']],
        'blockquote' => ['blockquote' => []],
        'horizontal-rule' => ['hr' => []],
        'code-block' => ['pre' => [], 'code' => []],
        'image' => ['img' => ['src', 'alt', 'title', 'width', 'height', 'style'], 'figure' => ['style'], 'figcaption' => ['style'], 'a' => ['href', 'target', 'rel']],
        'audio' => ['audio' => ['src', 'controls', 'preload', 'title', 'style']],
        'video' => ['video' => ['src', 'poster', 'controls', 'preload', 'width', 'height', 'title', 'style']],
        'file' => ['a' => ['href', 'download', 'type', 'title', 'rel', 'style'], 'span' => ['style']],
        'embed' => ['iframe' => ['src', 'title', 'allowfullscreen', 'loading', 'style']],
        'table' => ['div' => ['style'], 'table' => ['style'], 'colgroup' => [], 'col' => ['span', 'style'], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => ['style'], 'th' => ['colspan', 'rowspan', 'style'], 'td' => ['colspan', 'rowspan', 'style']],
        'details' => ['details' => [], 'summary' => []],
        'math' => self::MATHML,
        'callout' => ['aside' => ['style']],
        'layout' => ['section' => [], 'div' => [], 'article' => [], 'aside' => [], 'header' => [], 'footer' => [], 'main' => [], 'nav' => [], 'address' => [], 'center' => [], 'figure' => [], 'figcaption' => [], 'dl' => [], 'dt' => [], 'dd' => [], 'span' => [], 'small' => [], 'big' => [], 'abbr' => ['title'], 'cite' => [], 'q' => [], 'ins' => [], 'kbd' => [], 'samp' => [], 'var' => [], 'dfn' => [], 'time' => ['datetime'], 'bdi' => [], 'bdo' => ['dir'], 'font' => ['color', 'face', 'size']],
    ];

    /**
     * The CSS properties each feature writes.
     */
    private const CSS = [
        'color' => ['color'],
        'highlight' => ['background-color', 'color'],
        'font-family' => ['font-family'],
        'font-size' => ['font-size'],
        'text-align' => ['text-align'],
        'indent' => ['margin-left'],
        'text-indent' => ['text-indent'],
        'line-height' => ['line-height'],
        'spacing' => ['margin-top', 'margin-bottom', 'letter-spacing'],
        'task-list' => ['list-style-type', 'padding-left', 'display', 'align-items', 'gap'],
        'image' => ['display', 'width', 'max-width', 'height', 'margin', 'margin-left', 'margin-right', 'margin-top', 'text-align', 'font-size', 'color'],
        'video' => ['display', 'width', 'max-width', 'height', 'margin-left', 'margin-right'],
        'audio' => ['display', 'width'],
        'embed' => ['display', 'width', 'max-width', 'height', 'margin-left', 'margin-right', 'aspect-ratio', 'border'],
        'file' => ['display', 'align-items', 'gap', 'padding', 'border', 'border-radius', 'color', 'text-decoration', 'opacity'],
        'table' => ['overflow-x', 'border-collapse', 'width', 'min-width', 'border', 'padding', 'vertical-align', 'background-color', 'text-align'],
        'callout' => ['background-color', 'border-left', 'padding', 'border-radius'],
        'layout' => [
            'color', 'background', 'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
            'font', 'font-family', 'font-size', 'font-style', 'font-weight', 'font-variant',
            'text-align', 'text-indent', 'text-decoration', 'text-decoration-line', 'text-decoration-style', 'text-decoration-color', 'text-transform', 'text-shadow',
            'line-height', 'letter-spacing', 'word-spacing', 'white-space', 'word-break', 'overflow-wrap', 'word-wrap', 'vertical-align', 'direction',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-width', 'border-style', 'border-color',
            'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width', 'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color',
            'border-radius', 'border-top-left-radius', 'border-top-right-radius', 'border-bottom-right-radius', 'border-bottom-left-radius', 'border-collapse', 'border-spacing',
            'width', 'min-width', 'max-width', 'height', 'min-height', 'max-height', 'box-sizing',
            'display', 'float', 'clear', 'overflow', 'overflow-x', 'overflow-y', 'opacity', 'box-shadow', 'aspect-ratio', 'object-fit',
            'list-style', 'list-style-type', 'list-style-position',
            'flex', 'flex-direction', 'flex-wrap', 'flex-grow', 'flex-shrink', 'flex-basis', 'justify-content', 'align-items', 'align-self', 'gap', 'row-gap', 'column-gap',
            'table-layout', 'caption-side',
        ],
    ];

    private ?HtmlSanitizer $sanitizer = null;

    /**
     * @param  list<string>  $features
     * @param  array<string, list<string>>  $allow  tags and attributes a named format adds
     * @param  list<string>  $css  CSS properties a named format adds
     */
    public function __construct(
        public readonly array $features,
        private readonly array $allow = [],
        private readonly array $css = [],
    ) {}

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
     * The tags kept, each with the attributes kept on it; with layout, style on all.
     *
     * @return array<string, list<string>>
     */
    public function whitelist(): array
    {
        $tags = ['p' => [], 'br' => [], 'div' => []];

        foreach ([...array_map(fn (string $feature) => self::TAGS[$feature] ?? [], $this->features), $this->allow] as $more) {
            foreach ($more as $tag => $attributes) {
                $tags[$tag] = [...$tags[$tag] ?? [], ...$attributes];
            }
        }

        if ($this->has('layout')) {
            foreach ($tags as $tag => $attributes) {
                if ($tag !== 'br' && ! isset(self::MATHML[$tag])) {
                    $tags[$tag][] = 'style';
                }
            }
        }

        ksort($tags);

        return array_map(fn (array $attributes) => array_values(array_unique($attributes)), $tags);
    }

    /**
     * The CSS properties kept in a style.
     *
     * @return list<string>
     */
    public function css(): array
    {
        $properties = [...array_merge(...array_map(fn (string $feature) => self::CSS[$feature] ?? [], $this->features)), ...$this->css];

        sort($properties);

        return array_values(array_unique($properties));
    }

    /**
     * Cleans HTML to the whitelist. Images, players and embeds whose address didn't
     * pass go entirely, rather than stay as empty boxes; spans left without attributes
     * go too, their text kept.
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
     * What the Editor's script reads from data-format: the features, the whitelist, and
     * the values its controls offer.
     *
     * @return array<string, mixed>
     */
    public function client(): array
    {
        return [
            'features' => $this->features,
            'allow' => $this->whitelist(),
            'css' => $this->css(),
            'headings' => RichText::HEADINGS,
            'colors' => array_values(RichText::COLORS),
            'highlights' => array_values(RichText::HIGHLIGHTS),
            'highlightText' => RichText::HIGHLIGHT_TEXT,
            'fonts' => array_values(RichText::FONTS),
            'sizes' => RichText::SIZES,
            'lineHeights' => RichText::LINE_HEIGHTS,
            'indents' => RichText::INDENTS,
            'spacings' => RichText::SPACINGS,
            'letterSpacings' => RichText::LETTER_SPACINGS,
            'callouts' => RichText::CALLOUTS,
            'platforms' => array_values(RichText::platforms()),
            'styles' => ['cell' => self::CELL, 'headerCell' => self::HEADER_CELL, 'card' => self::CARD, 'callout' => self::CALLOUT, 'figure' => self::FIGURE, 'caption' => self::CAPTION],
        ];
    }

    private function config(): HtmlSanitizerConfig
    {
        $whitelist = $this->whitelist();

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->withAttributeSanitizer(new RichTextAttributes($this->css(), $this->hosts()));

        foreach ($whitelist as $tag => $attributes) {
            $config = $config->allowElement($tag, $attributes);
        }

        if (isset($whitelist['a'])) {
            $config = $config->forceAttribute('a', 'rel', 'noopener noreferrer nofollow');
        }

        // A task's box, never a field of a form around the content.
        if (isset($whitelist['input'])) {
            $config = $config->forceAttribute('input', 'type', 'checkbox')->forceAttribute('input', 'disabled', 'disabled');
        }

        foreach (['audio', 'video'] as $media) {
            if (isset($whitelist[$media])) {
                $config = $config->forceAttribute($media, 'controls', 'controls')->forceAttribute($media, 'preload', 'metadata');
            }
        }

        if (isset($whitelist['iframe'])) {
            $config = $config->forceAttribute('iframe', 'allowfullscreen', 'allowfullscreen')->forceAttribute('iframe', 'loading', 'lazy');
        }

        // These go with what is inside them. (Head elements, style and title, can't be
        // dropped inside a body: their text stays, escaped.)
        foreach (['script', 'template', 'noscript', 'iframe', 'object', 'embed', 'svg', 'math', 'textarea', 'select', 'button', 'input'] as $tag) {
            if (! isset($whitelist[$tag])) {
                $config = $config->dropElement($tag);
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
