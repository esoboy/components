<?php

namespace Esoboy\Ui\Appearance;

use Illuminate\Foundation\Http\Events\RequestHandled;

class InjectAppearance
{
    public function handle(RequestHandled $event): void
    {
        $appearance = Appearance::fromRequest($event->request);

        if ($appearance === Appearance::SYSTEM) {
            return;
        }

        $response = $event->response;

        if (! str_contains((string) $response->headers->get('content-type'), 'text/html')) {
            return;
        }

        $html = $response->getContent();

        if (! is_string($html) || ! preg_match('/<html\b[^>]*>/i', $html, $match) || str_contains($match[0], 'data-appearance')) {
            return;
        }

        $tag = substr($match[0], 0, -1).' data-appearance="'.$appearance.'">';

        $response->setContent(substr_replace($html, $tag, strpos($html, $match[0]), strlen($match[0])));
    }
}
