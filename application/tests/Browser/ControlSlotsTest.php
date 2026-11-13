<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Fixtures\ControlSlots;
use Tests\Fixtures\SlotCounter;

beforeEach(function () {
    Livewire::component('control-slots', ControlSlots::class);
    Livewire::component('slot-counter', SlotCounter::class);
    Route::middleware('web')->get('/control-slots', fn () => Blade::render(<<<'BLADE'
        <html><head>@vite('resources/css/app.css')</head><body>
            <livewire:control-slots />
            @uiScripts
        </body></html>
        BLADE));
});

it('keeps select labels and slot actions owned by the caller across updates', function () {
    visit('/control-slots')->click('#update-label')
        ->assertSeeIn('#custom-select', 'Updated')
        ->click('#custom-select')->click('#select-action')
        ->assertSeeIn('#message', 'select')->assertSeeIn('#custom-select', 'After')
        ->click('#select-submit')->assertSeeIn('#message', 'form')
        ->click('eso-select:has(#custom-select) [data-value="b"]')
        ->assertSeeIn('#choice', 'b')->assertSeeIn('#custom-select', 'Beta')
        ->assertNoJavaScriptErrors();
});

it('keeps command empty and footer actions on the caller after filtering', function () {
    $page = visit('/control-slots')->click('#slot-counter')->assertSeeIn('#slot-counter', 'Child 1');
    $page->click('#update-label')->assertSeeIn('#command-action', 'Updated');
    $page
        ->assertSeeIn('#slot-counter', 'Child 1')
        ->type('#custom-command [data-command-input]', 'missing')
        ->assertSeeIn('#slot-counter', 'Child 1')
        ->click('#empty-action')->assertSeeIn('#message', 'empty')
        ->assertSeeIn('#command-action', 'After')
        ->click('#command-action')->assertSeeIn('#message', 'command')
        ->click('#slot-counter')->assertSeeIn('#slot-counter', 'Child 2')
        ->click('#toggle-counter')->assertNotPresent('#slot-counter')
        ->click('#toggle-counter')->assertSeeIn('#slot-counter', 'Child 0')
        ->type('#bound-command [data-command-input]', 'bet')->assertSeeIn('#query', 'bet')
        ->keys('#bound-command [data-command-input]', 'Enter')->assertSeeIn('#message', 'bound')
        ->assertNoJavaScriptErrors();
});

it('updates native form submission when the parent disables and enables controls', function () {
    $entries = "Array.from(new FormData(document.querySelector('#native-values')).entries())";
    $expected = [['combo', 'a'], ['tags[]', 'a'], ['rating', '3'], ['tree', 'a']];
    visit('/control-slots')->assertScript($entries, $expected)
        ->click('#toggle-disabled')->assertScript($entries, [])
        ->click('#toggle-disabled')->assertScript($entries, $expected)
        ->assertNoJavaScriptErrors();
});
