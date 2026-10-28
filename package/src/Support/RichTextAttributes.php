<?php

namespace Esoboy\Ui\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps a rich text format's style declarations, each with an allowed value (written
 * as the format writes it, rgb() colors as hex), and embeds' players on their hosts.
 */
final class RichTextAttributes implements AttributeSanitizerInterface
{
    /**
     * @param  array<string, array<string, list<string>|string>>  $styles  element => property => values or a pattern
     * @param  list<string>  $hosts
     */
    public function __construct(
        private readonly array $styles,
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

        foreach (explode(';', $value) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $declared] = array_map(trim(...), explode(':', $declaration, 2));
            $property = strtolower($property);
            $allowed = $this->styles[$element][$property] ?? null;

            if ($allowed !== null && ($declared = self::match(self::normalize($declared), $allowed)) !== null) {
                $kept[$property] = "{$property}:{$declared}";
            }
        }

        return $kept === [] ? null : implode(';', $kept);
    }

    /**
     * @param  list<string>|string  $allowed
     */
    private static function match(string $value, array|string $allowed): ?string
    {
        if (is_string($allowed)) {
            return preg_match($allowed, $value) ? $value : null;
        }

        foreach ($allowed as $candidate) {
            if (self::normalize($candidate) === $value) {
                return $candidate;
            }
        }

        return null;
    }

    // Lower case, quotes and spaces around punctuation gone, 0px as 0, rgb() as hex.
    private static function normalize(string $value): string
    {
        $value = strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
        $value = preg_replace('/\s*([,()\/])\s*/', '$1', str_replace(['"', "'"], '', $value)) ?? '';
        $value = preg_replace('/(?<![\w.])0(?:px|em|rem|%)(?![\w%])/', '0', $value) ?? '';

        return preg_replace_callback('/^rgb\((\d{1,3}),(\d{1,3}),(\d{1,3})\)$/', fn (array $rgb) => sprintf('#%02x%02x%02x', ...array_map(fn (string $channel) => min(255, (int) $channel), array_slice($rgb, 1))), $value) ?? '';
    }

    private function plays(string $src): bool
    {
        return parse_url($src, PHP_URL_SCHEME) === 'https' && in_array(parse_url($src, PHP_URL_HOST), $this->hosts, true);
    }
}
