<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Fixtures\TabPanels;

/*
 * Tabs on the Navigation page: bound with wire:model or uncontrolled, moved with the
 * arrow keys along their orientation.
 */

beforeEach(function () {
    Livewire::component('tab-panels', TabPanels::class);
    Route::middleware('web')->get('/tab-panels', fn () => Blade::render(<<<'BLADE'
        <html><head>@vite('resources/css/app.css')</head><body>
            <livewire:tab-panels />
            @uiScripts
        </body></html>
        BLADE));
});

it('keeps the active uncontrolled panel visible after its parent updates', function () {
    visit('/tab-panels')
        ->assertSee('Hour content 0')
        ->click('#update-panels')->assertSeeIn('#updates', '1')
        ->assertSee('Hour content 1')
        ->assertDontSee('Minute content 1')
        ->click('#local-tabs [role="tab"][data-value="minute"]')
        ->click('#update-panels')->assertSeeIn('#updates', '2')
        ->assertScript('new FormData(document.querySelector("#tab-form")).get("local-tab")', 'minute')
        ->assertSee('Minute content 2')
        ->assertDontSee('Hour content 2')
        ->assertAttribute('#local-tabs [role="tab"][data-value="minute"]', 'aria-selected', 'true')
        ->click('#toggle-tabs')->assertNotPresent('#local-tabs')
        ->click('#toggle-tabs')->assertSee('Hour content 2')
        ->assertDontSee('Minute content 2')
        ->assertNoJavaScriptErrors();
});

it('follows the bound tab after parent updates without changing a neighbouring local tab', function () {
    visit('/tab-panels')
        ->click('#local-tabs [role="tab"][data-value="minute"]')
        ->click('#bound-tabs [role="tab"][data-value="minute"]')
        ->assertSeeIn('#active-tab', 'minute')
        ->assertSee('Bound minute 0')
        ->assertSee('Minute content 0')
        ->click('#update-panels')->assertSeeIn('#updates', '1')
        ->assertSee('Bound minute 1')
        ->assertSee('Minute content 1')
        ->click('#reset-bound-tab')->assertSeeIn('#active-tab', 'hour')
        ->assertSee('Bound hour 1')
        ->assertDontSee('Bound minute 1')
        ->assertSee('Minute content 1')
        ->assertDontSee('Hour content 1')
        ->assertNoJavaScriptErrors();
});

it('binds the active tab', function () {
    visit('/navigation')
        ->assertSee('Tab: invoices')
        ->click('[role="tab"]:text-is("Account")')
        ->assertSee('Make changes to your account here.')
        ->assertAttribute('[role="tab"]:text-is("Account")', 'aria-selected', 'true')
        ->assertScript("document.querySelector('[role=\"tabpanel\"]:has(table)').hidden")
        ->assertNoJavaScriptErrors();
});

it('moves with the arrow keys, past disabled tabs', function () {
    visit('/navigation')
        ->keys('[role="tab"]:text-is("Invoices")', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim()', 'Account')
        ->keys('*:focus', 'ArrowRight')
        ->assertScript('document.activeElement.textContent.trim()', 'Invoices');
});

it('switches uncontrolled line tabs', function () {
    visit('/navigation')
        ->click('[role="tab"]:text-is("Analytics")')
        ->assertSee('Analytics content.')
        ->assertDontSee('Overview content.');
});

it('moves vertical tabs with the up and down keys, activating on Enter as Base UI does', function () {
    visit('/navigation')
        ->keys('[role="tab"]:text-is("General")', 'ArrowDown')
        ->assertScript('document.activeElement.textContent.trim()', 'Security')
        ->assertSee('General settings.')
        ->keys('*:focus', 'Enter')
        ->assertSee('Security settings.');
});
