<?php

/*
 * Select on the Controls page: a listbox popover under its trigger, bound with
 * wire:model on the select.
 */

const SELECT_CONTENT = 'eso-select:has(#demo-select) [data-slot="select-content"]';

it('shows the bound item on its trigger', function () {
    visit('/controls')
        ->assertSeeIn('eso-select:has(#demo-select) [data-slot="select-trigger"]', 'Central Standard Time')
        ->assertNoJavaScriptErrors();
});

it('opens with the chosen item selected and picks another', function () {
    visit('/controls')
        ->click('eso-select:has(#demo-select) [data-slot="select-trigger"]')
        ->assertScript(opened(SELECT_CONTENT))
        ->assertAttribute('eso-select:has(#demo-select) [data-slot="select-item"][data-value="cst"]', 'aria-selected', 'true')
        ->click('eso-select:has(#demo-select) [data-slot="select-item"][data-value="gmt"]')
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'gmt')
        ->assertSeeIn('eso-select:has(#demo-select) [data-slot="select-trigger"]', 'Greenwich Mean Time');
});

it('moves with the arrow keys past disabled items and picks with Enter', function () {
    visit('/controls')
        ->click('eso-select:has(#demo-select) [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'")
        ->keys('*:focus', ['ArrowDown', 'ArrowDown', 'Enter'])
        ->assertSeeIn('#timezone', 'gmt');
});

it('keeps every visible frame aligned when reopened during a delayed Livewire update', function () {
    $page = visit('/controls')->resize(1280, 900);
    $page->script("document.querySelector('#demo-select').scrollIntoView({ block: 'center', behavior: 'instant' })");
    $page->click('#demo-select')->assertScript(opened(SELECT_CONTENT));

    $page->script(<<<'JS'
        (() => {
            const fetch = window.fetch;
            const hold = new Promise(resolve => {
                window.releaseSelectResponse = () => {
                    window.fetch = fetch;
                    resolve();
                };
            });
            window.fetch = async (...args) => {
                const response = await fetch(...args);
                await hold;
                return response;
            };

            const trigger = document.querySelector('#demo-select');
            const select = trigger.closest('eso-select');
            const panel = select.querySelector('[data-slot="select-content"]');
            window.selectFrames = [];
            function sample() {
                const style = getComputedStyle(panel);
                const visible = panel.matches(':popover-open') && style.visibility === 'visible' && Number(style.opacity) > 0;
                const value = trigger.querySelector('[data-slot="select-value"]').getBoundingClientRect();
                const picked = panel.querySelector('[data-slot="select-item"][data-value="gmt"] [data-slot="select-item-text"]').getBoundingClientRect();
                const { left, top } = panel.getBoundingClientRect();
                window.selectFrames.push({
                    visible, left, top, selection: select.dataset.value,
                    aligned: Math.abs(value.left - picked.left) < 1 && Math.abs(value.top - picked.top) < 1,
                });
                if (!window.stopSelectFrames) requestAnimationFrame(sample);
            }
            requestAnimationFrame(sample);
            select.querySelector('[data-slot="select-item"][data-value="gmt"]').click();
            trigger.click();
        })()
        JS);

    $page->page()->waitForFunction("window.selectFrames.filter(frame => frame.visible && frame.selection === 'cst').length >= 3");
    $page->script('window.releaseSelectResponse()');

    $page->assertSeeIn('#timezone', 'gmt');
    $page->page()->waitForFunction("window.selectFrames.some(frame => frame.visible && frame.selection === 'gmt')");

    $page->assertScript(opened(SELECT_CONTENT))
        ->assertScript(<<<'JS'
            (() => {
                window.stopSelectFrames = true;
                const firstVisible = window.selectFrames.findIndex(frame => frame.visible);
                const { left, top } = window.selectFrames[firstVisible];
                return window.selectFrames.slice(firstVisible).every(frame =>
                    frame.visible && frame.aligned && Math.abs(frame.left - left) < 1 && Math.abs(frame.top - top) < 1
                );
            })()
            JS)
        ->assertScript("document.activeElement.dataset.value === 'gmt'")
        ->assertAttribute('#demo-select', 'aria-expanded', 'true')
        ->assertNoJavaScriptErrors();
});

it('closes on Escape without changing the value', function () {
    visit('/controls')
        ->click('eso-select:has(#demo-select) [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'")
        ->keys('*:focus', ['ArrowDown', 'Escape'])
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'cst');
});

it('shows a validation error inside a field', function () {
    visit('/controls')
        ->click('eso-select:has(#fruit) [data-slot="select-trigger"]')
        ->click('eso-select:has(#fruit) [data-slot="select-item"][data-value="grapes"]')
        ->click('Pick')
        ->assertSee('The selected fruit is invalid.')
        ->assertAttribute('eso-select:has(#fruit) [data-slot="select-trigger"]', 'aria-invalid', 'true');
});

it('keeps the selected text aligned when the page scrolls and the trigger resizes', function () {
    $page = visit('/controls');
    $page->script("document.documentElement.style.scrollBehavior = 'instant'; document.querySelector('eso-select:has(#demo-select) [data-slot=select-trigger]').scrollIntoView({ block: 'center', behavior: 'instant' })");
    $page->click('eso-select:has(#demo-select) [data-slot="select-trigger"]');
    $aligned = <<<'JS'
        (() => {
            const select = document.querySelector('eso-select:has(#demo-select)');
            const value = select.querySelector('[data-slot="select-value"]').getBoundingClientRect();
            const selected = select.querySelector('[aria-selected="true"] [data-slot="select-item-text"]').getBoundingClientRect();
            return Math.abs(value.left - selected.left) < 1 && Math.abs(value.top - selected.top) < 1;
        })()
        JS;

    $page->assertScript($aligned);
    $page->script('window.scrollBy(0, 120)');
    $page->assertScript($aligned);
    $page->script("Object.assign(document.querySelector('eso-select:has(#demo-select) [data-slot=\"select-trigger\"]').style, { width: '240px', maxWidth: 'none', marginLeft: '40px' })");
    $page->assertScript($aligned)->assertNoJavaScriptErrors();
});

it('keeps item alignment inside a scrolling container without applying popover flips', function () {
    $page = visit('/controls')->resize(1280, 900);

    $page->script(<<<'JS'
        (() => {
            const select = document.querySelector('eso-select:has(#demo-select)');
            const host = document.createElement('div');
            host.style.cssText = 'height:320px;width:256px;overflow:auto;padding-top:120px';
            const space = document.createElement('div');
            space.style.height = '500px';
            select.before(host);
            host.append(select, space);
            host.scrollIntoView({ block: 'center' });
        })()
        JS);
    $page->click('eso-select:has(#demo-select) [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'");
    $page->script("document.querySelector('eso-select:has(#demo-select)').parentElement.scrollTop = 80");
    $page->assertScript(<<<'JS'
        (() => {
            const select = document.querySelector('eso-select:has(#demo-select)');
            const value = select.querySelector('[data-slot="select-value"]').getBoundingClientRect();
            const selected = select.querySelector('[aria-selected="true"] [data-slot="select-item-text"]').getBoundingClientRect();
            return Math.abs(value.left - selected.left) < 1 && Math.abs(value.top - selected.top) < 1;
        })()
        JS)->assertNoJavaScriptErrors();
});
