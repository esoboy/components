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
        ->click('[data-number-input="increment"]')
        ->click('[data-number-input="increment"]')
        ->assertSeeIn('#server-quantity', '3')
        ->click('#rating [data-value="4"]')
        ->assertSeeIn('#server-rating', '4')
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
