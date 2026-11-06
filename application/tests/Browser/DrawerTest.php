<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Fixtures\DrawerBinding;

/*
 * Drawer on the Overlays page: a modal dialog sliding from its swipe direction.
 */

it('opens from the bottom with its swipe handle and closes', function () {
    visit('/overlays')
        ->click('Open Drawer')
        ->assertScript(opened('#goal'))
        ->assertPresent('#goal [data-slot="drawer-swipe-handle"]')
        ->assertScript("(() => { const rect = document.querySelector('#goal').getBoundingClientRect(); return Math.abs(innerHeight - rect.bottom - 8) < 1 && Math.abs(rect.left - 8) < 1 })()")
        ->click('#goal [data-slot="drawer-footer"] button[command="close"]')
        ->assertScript(opened('#goal'), false)
        ->assertNoJavaScriptErrors();
});

it('opens from the right side', function () {
    visit('/overlays')
        ->click('Right Drawer')
        ->assertScript(opened('#side'))
        ->assertAttribute('#side', 'data-swipe-direction', 'right')
        ->assertScript("(() => { const rect = document.querySelector('#side').getBoundingClientRect(); return Math.abs(innerWidth - rect.right - 8) < 1 && Math.abs(rect.top - 8) < 1 })()")
        ->assertScript("getComputedStyle(document.querySelector('#side')).overflow", 'clip')
        ->keys('#side', 'Escape')
        ->assertScript(opened('#side'), false);
});

it('dismisses a drawer when clicking the backdrop', function () {
    $page = visit('/overlays')
        ->click('Open Drawer')
        ->click('#goal [data-slot="drawer-title"]')
        ->assertScript(opened('#goal'));

    // The backdrop starts above the dialog box. Send a real pointer click there.
    $page->page()->locator('#goal')->click(['position' => ['x' => 20, 'y' => -20]]);

    $page->assertScript(opened('#goal'), false);
});

it('syncs backdrop dismissal and keeps the slot owned by the parent through morphs', function (bool $live) {
    Livewire::component('drawer-binding', DrawerBinding::class);
    Route::middleware('web')->get('/bound-drawer', fn () => Blade::render(<<<'BLADE'
        <html><head>@vite('resources/css/app.css')</head><body>
            <livewire:drawer-binding :live="$live" />
            @uiScripts
        </body></html>
        BLADE, ['live' => $live]));

    $page = visit('/bound-drawer')
        ->click('#open-bound-drawer')
        ->assertAttribute('#drawer-server-state', 'data-open', 'true')
        ->assertScript(opened('#bound-drawer'))
        ->assertSeeIn('#drawer-slot-revision', 'Revision 1')
        ->assertPresent('#save-drawer-revision[disabled]');

    $page->script("Livewire.first().\$set('revision', 2)");
    $page->assertSeeIn('#drawer-slot-revision', 'Revision 2')
        ->assertScript(opened('#bound-drawer'))
        ->assertSeeIn('#save-drawer-revision', 'Save revision 2')
        ->assertNotPresent('#save-drawer-revision[disabled]')
        ->click('#save-drawer-revision')
        ->assertSeeIn('#drawer-saved-revision', '2')
        ->assertScript(opened('#bound-drawer'));

    $page->page()->locator('#bound-drawer')->click(['position' => ['x' => 20, 'y' => -20]]);
    $page->assertScript(opened('#bound-drawer'), false)
        ->assertScript("Livewire.first().\$get('drawerOpen')", false);

    if (! $live) {
        $page->assertAttribute('#drawer-server-state', 'data-open', 'true')
            ->click('#refresh-drawer-owner');
    }

    $page->assertAttribute('#drawer-server-state', 'data-open', 'false')
        ->assertScript(opened('#bound-drawer'), false)
        ->assertNoJavaScriptErrors();
})->with([
    'wire:model' => [false],
    'wire:model.live' => [true],
]);
