<?php

use App\Models\User;

/*
 * Data Table on the Data page: WithDataTable's search, sorting, faceted filters,
 * selection, row details, column visibility and pagination, and tree rows.
 */

const USERS = '[data-slot="data-table"] >> nth=0';

beforeEach(function () {
    foreach (range(1, 15) as $n) {
        User::factory()->create([
            'name' => sprintf('User %02d', $n),
            'email' => sprintf('user%02d@example.com', $n),
            'role' => $n % 3 === 0 ? 'admin' : 'member',
        ]);
    }
});

function visibleEmails(): string
{
    return "[...document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('tbody tr[data-row-id] td.lowercase')].map(cell => cell.textContent.trim().slice(4, 6)).join()";
}

it('pages ten users at a time', function () {
    visit('/data')
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10')
        ->click(USERS.' >> button:has-text("Go to next page")')
        ->assertScript(visibleEmails(), '11,12,13,14,15')
        ->assertNoJavaScriptErrors();
});

it('searches by email', function () {
    visit('/data')
        ->type('[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'user07')
        ->assertScript(visibleEmails(), '07')
        ->assertQueryStringHas('search', 'user07');
});

it('sorts from a column header', function () {
    visit('/data')
        ->click(USERS.' >> [data-slot="data-table-column-header"]:has-text("Name") button[command="toggle-popover"]')
        ->click('[data-slot="dropdown-menu-content"]:popover-open [role="menuitem"]:has-text("Desc")')
        ->assertScript(visibleEmails(), '15,14,13,12,11,10,09,08,07,06')
        ->assertQueryStringHas('sortBy', 'name');
});

it('filters by a facet with its counts, and resets', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-faceted-filter]')
        ->assertSeeIn('[data-slot="popover-content"]:popover-open [data-slot="command-item"][data-value="admin"]', '5')
        ->click('[data-slot="popover-content"]:popover-open [data-slot="command-item"][data-value="admin"]')
        ->assertScript(visibleEmails(), '03,06,09,12,15')
        ->click(USERS.' >> [data-data-table-reset]')
        ->assertScript("document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('tbody tr[data-row-id]').length", 10);
});

it('selects the page and shows the bulk actions', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-select-all]')
        ->assertSee('10 selected')
        ->assertScript("[...document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('[data-data-table-select-row]')].every(box => box.checked)")
        ->click('[aria-label="Clear selection"]')
        ->assertDontSee('10 selected');
});

it('expands a row into its details', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-row-toggle] >> nth=0')
        ->assertSee('Email verified')
        ->assertAttribute(USERS.' >> [data-data-table-row-toggle] >> nth=0', 'aria-expanded', 'true');
});

it('hides a column from the view options', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-view-options]')
        ->click('[data-slot="dropdown-menu-content"]:popover-open [data-slot="dropdown-menu-checkbox-item"]:has-text("Email")')
        ->assertScript("document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('td.lowercase').length", 0);
});

it('expands tree rows and binds their selection', function () {
    visit('/data')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform\"]').hidden", false)
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"]').hidden")
        ->click('tr[data-row-id="engineering/platform"] [data-data-table-row-toggle]')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"]').hidden", false)
        ->click('tr[data-row-id="marketing"] [data-data-table-select-row]')
        ->assertSeeIn('#teams', 'marketing');
});
