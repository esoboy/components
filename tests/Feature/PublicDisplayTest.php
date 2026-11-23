<?php

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('uses the default slot as the trigger and previews the specified media type', function (string $type, string $tag) {
    $src = '/media/42?version=2&size=full';
    $html = Blade::render(<<<'BLADE'
        <x-ui::media-preview :src="$src" :type="$type" aria-label="Sample">
            <x-ui::button id="open-preview"><x-ui::icon name="play" />Open file</x-ui::button>
        </x-ui::media-preview>
        BLADE, compact('src', 'type'));
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    $dialog = $document->querySelector('dialog');
    $media = $dialog->querySelector($tag);

    expect($document->querySelector('[data-ui-trigger] > #open-preview')->textContent)->toContain('Open file')
        ->and($dialog->querySelector('#open-preview'))->toBeNull()
        ->and($dialog->getAttribute('aria-label'))->toBe('Sample')
        ->and($dialog->querySelectorAll('img, audio, video')->length)->toBe(1)
        ->and($media->getAttribute('src'))->toBe($src)
        ->and($media->hasAttribute('controls'))->toBe($type !== 'image')
        ->and($media->hasAttribute('playsinline'))->toBe($type === 'video')
        ->and($dialog->querySelector('[aria-label="Zoom in"]') !== null)->toBe($type === 'image');
})->with([['image', 'img'], ['audio', 'audio'], ['video', 'video']]);

it('rejects an unsupported media preview type', function () {
    expect(fn () => Blade::render('<x-ui::media-preview src="/file" type="pdf">Preview</x-ui::media-preview>'))
        ->toThrow(ViewException::class, __('ui::errors.media_preview_type'));
});

it('formats statistics from the original decimal digits and distinguishes empty and loading values', function () {
    $view = '<x-ui::statistic title="Balance" :value="$value" :precision="$precision" group-separator="." decimal-separator="," suffix="€" />';
    foreach ([
        ['-12345678901234567890.567', 2, '-12.345.678.901.234.567.890,56'],
        ['.6', 3, '0,600'],
        [-1.25e-8, 9, '-0,000000012'],
        [1.2e21, 1, '1.200.000.000.000.000.000.000,0'],
        ['1e9', null, '1e9'],
        [2.345678901234567, null, '2,345678901234567'],
        ['8.20', null, '8,20'],
        [0, 0, '0'],
        [null, null, '—'],
    ] as [$value, $precision, $expected]) {
        expect(Blade::render($view, compact('value', 'precision')))->toContain('>'.$expected.'</span>', '€');
    }
    expect(Blade::render('<x-ui::statistic title="Balance" value="123" loading />'))->toContain('aria-busy="true"', 'class="ui-skeleton"')->not->toContain('>123<');
});

it('lets the caller format the original statistic value', function () {
    $format = static fn ($value) => '<'.$value.'>';
    $view = '<x-ui::statistic title="Code" value="003" :formatter="$format" />';
    expect(Blade::render($view, compact('format')))->toContain('&lt;003&gt;');
});

it('uses the same dimensions for icon presets and their numeric equivalents', function (string $size, string $pixels) {
    $html = Blade::render('<x-ui::icon name="star" :size="$size" />', compact('size'));
    expect($html)->toContain('width="'.$pixels.'"', 'height="'.$pixels.'"');
})->with([['sm', '14'], ['md', '16'], ['lg', '20'], ['24', '24'], ['12.5', '12.5']]);

it('registers Blade slides separately for nested carousels and skips controls for a single slide', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui::carousel aria-label="Outer">
            <x-ui::carousel.content>
                <x-ui::carousel.item>
                    <x-ui::carousel aria-label="Inner"><x-ui::carousel.content><x-ui::carousel.item>Only slide</x-ui::carousel.item></x-ui::carousel.content></x-ui::carousel>
                </x-ui::carousel.item>
                <x-ui::carousel.item>Second slide</x-ui::carousel.item>
            </x-ui::carousel.content>
        </x-ui::carousel>
    BLADE);
    expect(substr_count($html, 'class="ui-carousel-controls"'))->toBe(1)
        ->and(substr_count($html, 'class="ui-carousel-page"'))->toBe(2)
        ->and(substr_count($html, 'class="ui-carousel-item"'))->toBe(3);
});
