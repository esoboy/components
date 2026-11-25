<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Support\Icons;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

it('ships complete English and Chinese catalogs with matching placeholders', function (string $group) {
    $catalog = fn (string $locale) => Arr::dot(Lang::get("ui::$group", [], $locale, false));
    $english = $catalog('en');
    $chinese = $catalog('cn');
    expect(array_keys($chinese))->toEqualCanonicalizing(array_keys($english));

    foreach ($english as $key => $message) {
        preg_match_all('/:[a-zA-Z_]\w*/', $message, $en);
        preg_match_all('/:[a-zA-Z_]\w*/', $chinese[$key], $cn);
        expect($chinese[$key])->not->toBe('')
            ->and(array_unique($cn[0]))->toEqualCanonicalizing(array_unique($en[0]));
    }
})->with(['ui', 'editor', 'errors']);

it('renders public components and their accessible names in the app locale', function (string $locale, string $breadcrumb, string $more, string $carousel, string $month, string $error) {
    app()->setLocale($locale);
    $html = Blade::render(<<<'BLADE'
        <x-ui::breadcrumb><x-ui::breadcrumb.ellipsis /></x-ui::breadcrumb>
        <x-ui::carousel><x-ui::carousel.content><x-ui::carousel.item>Acme</x-ui::carousel.item></x-ui::carousel.content></x-ui::carousel>
        <x-ui::calendar initial-month="2026-11-01" />
        <x-ui::editor aria-label="Acme draft" />
        <x-ui::rich-text value='<iframe src="https://example.test/player"></iframe>' />
        BLADE);
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    expect($dom->querySelector('nav')->getAttribute('aria-label'))->toBe($breadcrumb)
        ->and($dom->querySelector('.ui-breadcrumb-ellipsis')->textContent)->toContain($more)
        ->and($dom->querySelector('.ui-carousel')->getAttribute('aria-roledescription'))->toBe($carousel)
        ->and($dom->querySelector('[role="grid"]')->getAttribute('aria-label'))->toBe($month)
        ->and($dom->querySelector('esoboy-editor')->getAttribute('aria-label'))->toBe('Acme draft')
        ->and($dom->querySelector('esoboy-editor')->hasAttribute('data-messages'))->toBeFalse()
        ->and(fn () => Icons::content('absent-test-icon'))->toThrow(InvalidArgumentException::class, $error);
})->with([
    ['en', 'Breadcrumb', 'More', 'carousel', 'November 2026', 'Unknown Lucide icon: absent-test-icon.'],
    ['cn', '面包屑导航', '更多', '轮播', '2026年11月', '未知的 Lucide 图标：absent-test-icon。'],
]);

it('uses the calendar locale for month captions when it differs from the application', function (string $appLocale, string $calendarLocale, string $caption) {
    app()->setLocale($appLocale);
    $html = Blade::render('<x-ui::calendar :locale="$calendarLocale" initial-month="2026-11-01" />', compact('calendarLocale'));
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    expect($dom->querySelector('[role="grid"]')->getAttribute('aria-label'))->toBe($caption);
})->with([['en', 'zh_CN', '2026年11月'], ['cn', 'en', 'November 2026']]);

it('loads both languages without publishing and passes only application overrides to the editor', function () {
    $languageFiles = Storage::fake('ui-language-files');
    $this->app->useLangPath($languageFiles->path(''));
    Lang::getLoader()->addPath($languageFiles->path(''));
    $this->artisan('vendor:publish', ['--tag' => 'laravel-assets', '--force' => true])->assertSuccessful();
    expect($languageFiles->directoryExists('vendor/ui'))->toBeFalse()
        ->and(__('ui::ui.select', [], 'cn'))->toBe('请选择')
        ->and(__('ui::ui.select', [], 'en'))->toBe('Select an option');

    app()->setLocale('cn');
    $label = '粗体 "<测试>" &';
    $languageFiles->put('vendor/ui/cn/editor.php', '<?php return '.var_export(['bold' => $label], true).';');
    $html = Blade::render('<x-ui::editor /><x-ui::breadcrumb aria-label="目录位置" />');
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    $messages = $dom->querySelector('esoboy-editor')->getAttribute('data-messages');
    expect(json_decode($messages, true, flags: JSON_THROW_ON_ERROR))->toBe(['bold' => $label])
        ->and($dom->querySelector('nav')->getAttribute('aria-label'))->toBe('目录位置');
});
