<?php

use Illuminate\Support\Facades\File;

function packageLocales(): array
{
    return collect(glob(__DIR__.'/../../lang/*.json'))
        ->mapWithKeys(fn (string $file) => [basename($file, '.json') => json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR)])
        ->all();
}

it('ships english and simplified chinese', function () {
    expect(array_keys(packageLocales()))->toContain('en', 'zh_CN');
});

it('keeps every locale in sync with english', function () {
    $locales = packageLocales();

    foreach ($locales as $locale => $lines) {
        expect(array_keys($lines))->toEqualCanonicalizing(array_keys($locales['en']), "Locale {$locale} is out of sync with en.");
    }
});

it('prefixes every key with ui.', function () {
    foreach (packageLocales() as $lines) {
        foreach (array_keys($lines) as $key) {
            expect($key)->toStartWith('ui.');
        }
    }
});

it('only references keys that exist', function () {
    $keys = array_keys(packageLocales()['en']);

    $missing = collect(componentViews())
        ->flatMap(function (string $path, string $name) {
            preg_match_all('/__\(\s*[\'"](ui\.[^\'"]+)[\'"]/', file_get_contents($path), $matches);

            return array_map(fn (string $key) => "{$name}: {$key}", $matches[1]);
        })
        ->reject(fn (string $usage) => in_array(substr($usage, strpos($usage, ': ') + 2), $keys, true));

    expect($missing->all())->toBe([]);
});

it('resolves package strings per locale', function () {
    app()->setLocale('zh_CN');
    expect(__('ui.close'))->toBe('关闭');

    app()->setLocale('en');
    expect(__('ui.close'))->toBe('Close');
});

it('lets the app override strings in lang/vendor/ui', function () {
    $override = lang_path('vendor/ui/zh_CN.json');

    File::ensureDirectoryExists(dirname($override));
    File::put($override, json_encode(['ui.close' => '关掉'], JSON_UNESCAPED_UNICODE));

    try {
        app()->setLocale('zh_CN');

        expect(__('ui.close'))->toBe('关掉')
            ->and(__('ui.loading'))->toBe('加载中');
    } finally {
        File::deleteDirectory(lang_path('vendor/ui'));
    }
});
