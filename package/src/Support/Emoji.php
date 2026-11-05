<?php

namespace Esoboy\Ui\Support;

use Esoboy\Ui\Assets;

final class Emoji
{
    /** @return array<string, list<array{string, string, string, float}>> */
    public static function catalog(): array
    {
        $assets = app(Assets::class);
        $locale = strtolower(str_replace('_', '-', app()->getLocale()));
        $file = collect(["emoji/{$locale}.json", 'emoji/'.strtok($locale, '-').'.json', 'emoji/en.json'])->map(fn ($file) => $assets->path($file))->first(fn ($path) => $path !== null);

        $contents = $file ? file_get_contents($file) : false;

        return $contents !== false ? json_decode($contents, true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
