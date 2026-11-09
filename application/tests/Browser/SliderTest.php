<?php

/*
 * Slider on the Controls page: native range thumbs bound with wire:model, a number for
 * one thumb and a list for two, which can't cross.
 */

it('binds one thumb from the keyboard', function () {
    visit('/controls')
        ->assertSeeIn('#volume', '50')
        ->keys('#volume-slider', ['ArrowRight', 'ArrowRight'])
        ->assertSeeIn('#volume', '52')
        ->assertNoJavaScriptErrors();
});

it('binds two thumbs as a list by their step', function () {
    visit('/controls')
        ->assertSeeIn('#price', '25-75')
        ->keys('#price-slider [data-slot="slider-thumb"] >> nth=1', 'ArrowLeft')
        ->assertSeeIn('#price', '25-70');
});

it('keeps a thumb from crossing the other', function () {
    visit('/controls')
        ->keys('#price-slider [data-slot="slider-thumb"] >> nth=0', 'End')
        ->assertSeeIn('#price', '75-75');
});

it('follows the pointer along its track', function () {
    $page = visit('/controls');

    $box = $page->page()->locator('eso-slider:has(#volume-slider)')->boundingBox();

    $page->page()->locator('eso-slider:has(#volume-slider)')->click(['position' => ['x' => 6 + ($box['width'] - 12) * 0.8, 'y' => $box['height'] / 2]]);

    $page->assertSeeIn('#volume', '80');
});

it('can pull either thumb away after both thumbs meet', function () {
    $page = visit('/controls')
        ->keys('#price-slider [data-slot="slider-thumb"] >> nth=0', 'End')
        ->assertSeeIn('#price', '75-75');

    $slider = $page->page()->locator('#price-slider');
    $box = $slider->boundingBox();
    $point = fn (float $fraction) => ['x' => 6 + ($box['width'] - 12) * $fraction, 'y' => $box['height'] / 2];

    $slider->dragTo($slider, ['sourcePosition' => $point(0.75), 'targetPosition' => $point(0.35)]);
    $page->assertSeeIn('#price', '35-75');

    $page->keys('#price-slider [data-slot="slider-thumb"] >> nth=1', 'Home')
        ->assertSeeIn('#price', '35-35');

    $slider->dragTo($slider, ['sourcePosition' => $point(0.35), 'targetPosition' => $point(0.9)]);
    $page->assertSeeIn('#price', '35-90')
        ->assertScript("Alpine.evaluate(document.getElementById('price-slider'), '\$wire.price.every(Number.isFinite)')")
        ->assertNoJavaScriptErrors();
});

it('follows the pointer between steps and snaps to the value on release', function () {
    $page = visit('/controls');
    $slider = $page->page()->locator('#price-slider');
    $box = $slider->boundingBox();
    $start = ['x' => 6 + ($box['width'] - 12) * 0.25, 'y' => $box['height'] / 2];
    $end = ['x' => $start['x'] + ($box['width'] - 12) * 0.02, 'y' => $start['y']];

    $page->script(<<<'JS'
        (() => {
            const slider = document.getElementById('price-slider');
            const fill = slider.querySelector('[data-slot="slider-range"]');
            slider.addEventListener('pointermove', event => {
                if (event.buttons === 1) {
                    window.sliderDragSample = {
                        gap: Math.abs(fill.getBoundingClientRect().left - event.clientX),
                        value: slider.querySelector('input').valueAsNumber,
                    };
                }
            });
        })()
        JS);
    $slider->dragTo($slider, ['sourcePosition' => $start, 'targetPosition' => $end]);

    $page->assertScript('window.sliderDragSample?.gap < 1.5 && window.sliderDragSample.value === 25')
        ->assertScript("Number(document.querySelector('#price-slider [data-slot=slider-range]').style.getPropertyValue('--start')) === 25")
        ->assertNoJavaScriptErrors();
});
