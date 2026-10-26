<?php

/*
 * Library-wide rules every component has to follow.
 */

it('enables blaze in every component', function (string $path) {
    expect(file_get_contents($path))->toStartWith('@blaze');
})->with(fn () => componentViews());

it('does not hardcode user facing text in any component', function (string $path) {
    $source = file_get_contents($path);

    $text = preg_replace([
        '/\{\{--.*?--\}\}/s',           // blade comments
        '/@php\b.*?@endphp/s',         // php blocks
        '/<\?php.*?\?>/s',              // raw php
        '/\{\{.*?\}\}|\{!!.*?!!\}/s',   // echoes, where translated text belongs
        '/<[^>]*>/s',                   // html tags and their attributes
        '/@\w+(\s*\((?:[^()]|(?1))*\))?/', // directives and their arguments
    ], '', $source);

    expect(trim($text))->toBe('', "Hardcoded text found in {$path}: use a ui.* translation key instead.");
})->with(fn () => componentViews());
