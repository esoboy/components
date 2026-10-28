<?php

namespace Esoboy\Ui\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps a style's declarations whose property is whitelisted, their values as written
 * unless they could run or load something (expression(), javascript:, a url() that isn't
 * http(s) or relative, escapes, comments); and embeds' players on their hosts only.
 */
final class RichTextAttributes implements AttributeSanitizerInterface
{
    /**
     * @param  list<string>  $css
     * @param  list<string>  $hosts
     */
    public function __construct(
        private readonly array $css,
        private readonly array $hosts,
    ) {}

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): array
    {
        return ['style', 'src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ($attribute === 'src') {
            return $element !== 'iframe' || $this->plays($value) ? $value : null;
        }

        $kept = [];

        foreach (self::declarations($value) as [$property, $declared]) {
            if (in_array($property, $this->css, true) && self::safe($declared)) {
                $kept[$property] = "{$property}:{$declared}";
            }
        }

        return $kept === [] ? null : implode(';', $kept);
    }

    /**
     * A style's declarations as [property, value], split on the semicolons outside
     * parentheses and quotes.
     *
     * @return list<array{string, string}>
     */
    public static function declarations(string $style): array
    {
        $parts = [];
        $part = '';
        $depth = 0;
        $quote = null;

        foreach (mb_str_split($style) as $character) {
            if ($quote !== null) {
                $quote = $character === $quote ? null : $quote;
            } elseif ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($character === ';' && $depth === 0) {
                $parts[] = $part;
                $part = '';

                continue;
            }

            $part .= $character;
        }

        $declarations = [];

        foreach ([...$parts, $part] as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = explode(':', $declaration, 2);
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

            // Unbalanced, a value could carry a declaration of its own: it goes.
            if ($value !== '' && ! str_contains($value, ';')) {
                $declarations[] = [strtolower(trim($property)), $value];
            }
        }

        return $declarations;
    }

    private static function safe(string $value): bool
    {
        if (preg_match('~expression\s*\(|javascript:|vbscript:|-moz-binding|behavior|@import|[\\\\<>{}]|/\*~i', $value)) {
            return false;
        }

        preg_match_all('~url\(\s*([\'"]?)(.*?)\1\s*\)~i', $value, $urls);

        foreach ($urls[2] as $url) {
            $scheme = parse_url(trim($url), PHP_URL_SCHEME);

            if (is_string($scheme) && ! in_array(strtolower($scheme), ['http', 'https'], true)) {
                return false;
            }
        }

        return true;
    }

    private function plays(string $src): bool
    {
        return parse_url($src, PHP_URL_SCHEME) === 'https' && in_array(parse_url($src, PHP_URL_HOST), $this->hosts, true);
    }
}
