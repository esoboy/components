<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Fixtures\ModelBindings;

beforeEach(function () {
    Livewire::component('model-bindings', ModelBindings::class);
    Route::middleware('web')->get('/model-binding-ui', fn () => Blade::render(<<<'BLADE'
        <html><head>@vite('resources/css/app.css')</head><body>
            <livewire:model-bindings />
            @uiScripts
        </body></html>
        BLADE));
});

it('commits editor blur bindings when focus leaves the entire editor', function () {
    visit('/model-binding-ui')
        ->assertPresent('#body-editor .ProseMirror')
        ->click('#body-editor .ProseMirror')
        ->keys('#body-editor .ProseMirror', ['End', '!'])
        ->assertSeeIn('#server-body', '<p>Before</p>')
        ->click('#body-editor [data-editor-command="bold"]')
        ->assertSeeIn('#server-body', '<p>Before</p>')
        ->click('#outside')
        ->assertSeeIn('#server-body', 'Before!')
        ->assertNoJavaScriptErrors();
});

it('uses Livewire debounce and lazy modifiers for composite native controls', function () {
    visit('/model-binding-ui')
        ->click('eso-number-input:has(#quantity) [data-number-input="increment"]')
        ->click('eso-number-input:has(#quantity) [data-number-input="increment"]')
        ->assertSeeIn('#server-quantity', '3')
        ->click('#rating [data-value="4"]')
        ->assertSeeIn('#server-rating', '4')
        ->assertNoJavaScriptErrors();
});

it('commits a step on blur and keeps two controls bound to the same property', function () {
    $page = visit('/model-binding-ui')
        ->click('eso-number-input:has(#blur-quantity) [data-number-input="increment"]')
        ->assertValue('#blur-quantity', '2')
        ->assertSeeIn('#server-blur-quantity', '1')
        ->click('#outside')
        ->assertSeeIn('#server-blur-quantity', '2');

    $page->script("Livewire.first().\$set('quantity', 7)");
    $page->assertValue('#duplicate-quantity input >> nth=0', '7')
        ->assertValue('#duplicate-quantity input >> nth=1', '7')
        ->assertScript("new Set([...document.querySelectorAll('#duplicate-quantity eso-number-input')].map(el => el.getAttribute('wire:id'))).size", 2)
        ->assertNoJavaScriptErrors();
});

it('keeps caller command bindings and actions with server filtering enabled', function () {
    $page = visit('/model-binding-ui')
        ->fill('#bound-command input', 'arch')
        ->assertSeeIn('#server-command', 'arch')
        ->assertAttribute('#bound-command [data-value="copy"]', 'hidden', '')
        ->click('#bound-command [data-value="archive"]')
        ->assertSeeIn('#server-command-action', 'archive');

    $page->fill('#bound-command input', 'copy')
        ->assertAttribute('#bound-command [data-value="archive"]', 'hidden', '');
    $page->script(<<<'JS'
        (() => {
            const input = document.querySelector('#bound-command input');
            input.value = 'missing';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
        })()
        JS);
    $page->assertAttribute('#bound-command [data-value="copy"]', 'hidden', '')
        ->assertSeeIn('#server-command-action', 'archive')
        ->fill('#bound-command input', 'copy')
        ->click('#bound-command [data-value="copy"] button')
        ->assertSeeIn('#server-command-action', 'copy')
        ->assertNoJavaScriptErrors();
});

it('adds and clears initially empty parent slots when the parent morphs', function () {
    visit('/model-binding-ui')
        ->assertMissing('#calendar-header')
        ->click('#toggle-header')
        ->assertSeeIn('#calendar-header', 'Custom calendar header')
        ->click('#toggle-header')
        ->assertMissing('#calendar-header')
        ->assertNoJavaScriptErrors();
});

it('discards the character counter draft after parent replacements', function () {
    visit('/model-binding-ui')
        ->assertSeeIn('#draft-count', '1')
        ->fill('#counted', 'bbbb')
        ->assertSeeIn('#draft-count', '4')
        ->click('#replace-draft')
        ->assertValue('#counted', 'ccc')
        ->assertSeeIn('#draft-count', '3')
        ->click('#reset-draft')
        ->assertValue('#counted', 'a')
        ->assertSeeIn('#draft-count', '1')
        ->assertNoJavaScriptErrors();
});

it('initializes a plain Blade control without a caller Alpine setup or a Livewire component', function () {
    Route::middleware('web')->get('/blade-only-ui', fn () => Blade::render(<<<'BLADE'
        <html><head></head><body>
            <x-ui::select name="status" :options="['draft' => 'Draft', 'published' => 'Published']" placeholder="Choose" />
            @uiScripts
        </body></html>
        BLADE));

    visit('/blade-only-ui')
        ->click('[data-slot="select-trigger"]')
        ->click('[data-slot="select-item"][data-value="published"]')
        ->assertSeeIn('[data-slot="select-value"]', 'Published')
        ->assertSelected('select[name="status"]', 'published')
        ->assertNoJavaScriptErrors();
});

it('updates PHP combobox labels and chips after the parent replaces their values', function () {
    $page = visit('/controls');
    $page->script("Livewire.first().\$set('framework', 'nuxt')");
    $page->assertValue('#demo-combobox [role="combobox"]', 'Nuxt.js');
    $page->script("Livewire.first().\$set('frameworks', ['astro', 'remix'])");
    $page->assertSeeIn('#demo-chips [data-slot="combobox-chips"]', 'Astro')->assertSeeIn('#demo-chips [data-slot="combobox-chips"]', 'Remix');
    $page->script("Livewire.first().\$set('framework', null)");
    $page->assertValue('#demo-combobox [role="combobox"]', '')
        ->assertNoJavaScriptErrors();
});

it('updates PHP date labels and time controls after a parent reset', function () {
    $page = visit('/controls');
    $page->script("Livewire.first().\$set('startsAt', '2026-11-12 15:45')");
    $page->assertSeeIn('#demo-datetime [data-slot="date-picker-value"]', 'November 12, 2026')
        ->click('#demo-datetime [data-date-picker-trigger]')
        ->assertValue('#demo-datetime [data-date-picker-time="from"]', '15:45')
        ->assertPresent('#demo-datetime button[data-day="2026-11-12"]')
        ->assertNoJavaScriptErrors();
});

it('binds an accordion through native details and follows parent changes', function () {
    $page = visit('/layout');
    $page->click('summary:has-text("What is your return policy?")')
        ->assertAttribute('[data-slot="accordion-item"][data-value="returns"]', 'open', '');
    $page->script("Livewire.first().\$set('section', 'shipping')");
    $page->assertAttribute('[data-slot="accordion-item"][data-value="shipping"]', 'open', '')
        ->assertAttributeMissing('[data-slot="accordion-item"][data-value="returns"]', 'open')
        ->assertNoJavaScriptErrors();
});

it('clears a selected single toggle group without caller event wiring', function () {
    visit('/controls')
        ->click('[data-slot="toggle-group-item"]:has-text("Center")')
        ->assertSeeIn('#align', 'center')
        ->click('[data-slot="toggle-group-item"]:has-text("Center")')
        ->assertDontSeeIn('#align', 'center')
        ->assertNotChecked('[data-slot="toggle-group-item"]:has-text("Center") input')
        ->assertNoJavaScriptErrors();
});
