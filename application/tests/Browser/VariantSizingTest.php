<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/variant-sizing', fn () => Blade::render(<<<'BLADE'
        <html><head>@vite('resources/css/app.css')</head><body>
            @foreach (['default', 'outline', 'secondary', 'ghost', 'destructive'] as $variant)
                <x-ui::button :variant="$variant">Example</x-ui::button>
            @endforeach

            @foreach (['default', 'outline'] as $variant)
                <x-ui::toggle :variant="$variant">Example</x-ui::toggle>

                @foreach (['horizontal', 'vertical'] as $orientation)
                    <x-ui::toggle-group multiple :variant="$variant" :orientation="$orientation" :spacing="0">
                        <x-ui::toggle-group.item value="first">First choice</x-ui::toggle-group.item>
                        <x-ui::toggle-group.item value="second">Second choice</x-ui::toggle-group.item>
                    </x-ui::toggle-group>
                @endforeach
            @endforeach
        </body></html>
        BLADE));
});

it('paints button variants to the edge of equally sized controls', function () {
    $page = visit('/variant-sizing');

    $buttons = $page->script(<<<'JS'
        Array.from(document.querySelectorAll('[data-slot="button"]'), button => {
            const { width, height } = button.getBoundingClientRect();
            return { width, height, variant: button.dataset.variant, clip: getComputedStyle(button).backgroundClip };
        })
        JS);

    foreach ($buttons as $button) {
        expect($button['clip'])->toBe($button['variant'] === 'outline' ? 'padding-box' : 'border-box');
    }

    expect(array_unique(array_column($buttons, 'width')))->toHaveCount(1)
        ->and(array_unique(array_column($buttons, 'height')))->toBe([32]);
});

it('keeps toggle sizes and joined edges consistent across variants', function () {
    $page = visit('/variant-sizing');

    foreach (['toggle', 'toggle-group'] as $slot) {
        $dimensions = $page->script(<<<JS
            Array.from(document.querySelectorAll('[data-slot="$slot"]'), element => {
                const { width, height } = element.getBoundingClientRect();
                return [width, height];
            })
            JS);

        [$default, $outline] = array_chunk($dimensions, count($dimensions) / 2);
        expect($default)->toBe($outline);
    }

    $page->assertScript(<<<'JS'
        ['horizontal', 'vertical'].every(orientation => {
            const group = document.querySelector(
                `[data-slot="toggle-group"][data-variant="outline"][data-orientation="${orientation}"]`
            );
            const side = orientation === 'horizontal' ? 'Left' : 'Top';
            return getComputedStyle(group.firstElementChild)[`border${side}Width`] === '1px'
                && getComputedStyle(group.lastElementChild)[`border${side}Width`] === '0px';
        })
        JS);
});
