<?php

/*
 * Select on the Controls page: a listbox popover under its trigger, bound with
 * wire:model on the select.
 */

const SELECT_CONTENT = '#demo-select [data-slot="select-content"]';

it('shows the bound item on its trigger', function () {
    visit('/controls')
        ->assertSeeIn('#demo-select [data-slot="select-trigger"]', 'Central Standard Time')
        ->assertNoJavaScriptErrors();
});

it('opens with the chosen item selected and picks another', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->assertScript(opened(SELECT_CONTENT))
        ->assertAttribute('#demo-select [data-slot="select-item"][data-value="cst"]', 'aria-selected', 'true')
        ->click('#demo-select [data-slot="select-item"][data-value="gmt"]')
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'gmt')
        ->assertSeeIn('#demo-select [data-slot="select-trigger"]', 'Greenwich Mean Time');
});

it('moves with the arrow keys past disabled items and picks with Enter', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'")
        ->keys('*:focus', ['ArrowDown', 'ArrowDown', 'Enter'])
        ->assertSeeIn('#timezone', 'gmt');
});

it('closes on Escape without changing the value', function () {
    visit('/controls')
        ->click('#demo-select [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'")
        ->keys('*:focus', ['ArrowDown', 'Escape'])
        ->assertScript(opened(SELECT_CONTENT), false)
        ->assertSeeIn('#timezone', 'cst');
});

it('shows a validation error inside a field', function () {
    visit('/controls')
        ->click('#fruit [data-slot="select-trigger"]')
        ->click('#fruit [data-slot="select-item"][data-value="grapes"]')
        ->click('Pick')
        ->assertSee('The selected fruit is invalid.')
        ->assertAttribute('#fruit [data-slot="select-trigger"]', 'aria-invalid', 'true');
});

it('keeps the selected text aligned when the page scrolls and the trigger resizes', function () {
    $page = visit('/controls')->click('#demo-select [data-slot="select-trigger"]');
    $aligned = <<<'JS'
        (() => {
            const select = document.querySelector('#demo-select');
            const value = select.querySelector('[data-slot="select-value"]').getBoundingClientRect();
            const selected = select.querySelector('[aria-selected="true"] [data-slot="select-item-text"]').getBoundingClientRect();
            return Math.abs(value.left - selected.left) < 1 && Math.abs(value.top - selected.top) < 1;
        })()
        JS;

    $page->assertScript($aligned);
    $page->script('window.scrollBy(0, 120)');
    $page->assertScript($aligned);
    $page->script("Object.assign(document.querySelector('#demo-select [data-slot=\"select-trigger\"]').style, { width: '240px', maxWidth: 'none', marginLeft: '40px' })");
    $page->assertScript($aligned)->assertNoJavaScriptErrors();
});

it('keeps item alignment inside a scrolling container without applying popover flips', function () {
    $page = visit('/controls')->resize(1280, 900);

    $page->script(<<<'JS'
        (() => {
            const select = document.querySelector('#demo-select');
            const host = document.createElement('div');
            host.style.cssText = 'height:320px;width:256px;overflow:auto;padding-top:120px';
            const space = document.createElement('div');
            space.style.height = '500px';
            select.before(host);
            host.append(select, space);
            host.scrollIntoView({ block: 'center' });
        })()
        JS);
    $page->click('#demo-select [data-slot="select-trigger"]')
        ->assertScript("document.activeElement?.dataset.value === 'cst'");
    $page->script("document.querySelector('#demo-select').parentElement.scrollTop = 80");
    $page->assertScript(<<<'JS'
        (() => {
            const select = document.querySelector('#demo-select');
            const value = select.querySelector('[data-slot="select-value"]').getBoundingClientRect();
            const selected = select.querySelector('[aria-selected="true"] [data-slot="select-item-text"]').getBoundingClientRect();
            return Math.abs(value.left - selected.left) < 1 && Math.abs(value.top - selected.top) < 1;
        })()
        JS)->assertNoJavaScriptErrors();
});
