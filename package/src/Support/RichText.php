<?php

namespace Esoboy\Ui\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The HTML the Editor writes, and nothing else: its blocks, marks and links, without
 * attributes but a link's and a list's start. Anything else is unwrapped, its text kept,
 * but scripts and embeds go with their content; links keep web, mail and phone addresses
 * only and always carry rel="noopener noreferrer nofollow". The Editor's view sanitizes
 * what goes in, #[Sanitize] what comes back.
 */
final class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return (self::$sanitizer ??= new HtmlSanitizer(self::config()))->sanitize($html);
    }

    private static function config(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowElement('a', ['href', 'target', 'rel'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->allowElement('ol', ['start']);

        // Its elements, the tags pasted or older HTML says the same with, and divs and
        // smaller headings so imported text keeps its blocks (the Editor folds them in).
        foreach (['p', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'code', 'pre', 'blockquote', 'ul', 'li', 'hr'] as $element) {
            $config = $config->allowElement($element);
        }

        // Head elements (style, title) can't be dropped inside a body: their text stays, escaped.
        foreach (['script', 'template', 'noscript', 'iframe', 'object', 'embed', 'svg', 'math', 'textarea', 'select'] as $element) {
            $config = $config->dropElement($element);
        }

        return $config;
    }
}
