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
    return "[...document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('tbody tr[data-row-id] .lowercase')].map(cell => cell.textContent.trim().slice(4, 6)).join()";
}

it('jumps to a numbered page and updates the active page', function () {
    $pagination = USERS.' >> [data-slot="pagination"]';

    visit('/data')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '15 total')
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10')
        ->assertDisabled($pagination.' [aria-label="Go to previous page"]')
        ->click($pagination.' button:text-is("2")')
        ->assertScript(visibleEmails(), '11,12,13,14,15')
        ->assertSeeIn($pagination.' [aria-current="page"]', '2')
        ->assertDisabled($pagination.' [aria-label="Go to next page"]')
        ->click($pagination.' [aria-label="Go to previous page"]')
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10')
        ->assertNoJavaScriptErrors();
});

it('aligns the selected page size over its trigger with inset options', function () {
    $size = USERS.' >> [data-slot="data-table-page-size"]';
    $page = visit('/data')->resize(1280, 900);
    // Leave room for the list so viewport collision handling does not shift it.
    $center = "document.querySelector('[data-slot=\"data-table-page-size\"]').scrollIntoView({ block: 'center' })";
    $page->script($center);
    $page->click($size.' [data-slot="select-trigger"][aria-label="Rows per page"]');
    $aligned = <<<'JS'
        (() => {
            const select = document.querySelector('[data-slot="data-table-page-size"] eso-select');
            const value = select.querySelector('[data-slot="select-value"]').getBoundingClientRect();
            const panel = select.querySelector('[data-slot="select-content"]').getBoundingClientRect();
            const item = select.querySelector('[data-slot="select-item"][aria-selected="true"]');
            const selected = item.querySelector('[data-slot="select-item-text"]').getBoundingClientRect();
            const option = item.getBoundingClientRect();
            return Math.abs(value.left - selected.left) < 1
                && Math.abs(value.top - selected.top) < 1
                && option.left - panel.left >= 4
                && panel.right - option.right >= 4;
        })()
        JS;

    $page->assertScript($aligned)
        ->click($size.' [data-slot="select-item"][data-value="20"]')
        ->assertSeeIn($size.' [data-slot="select-trigger"]', '20')
        ->assertMissing(USERS.' >> [data-slot="pagination"]')
        ->assertScript("document.querySelector('[data-slot=\"data-table\"]').querySelectorAll('tbody tr[data-row-id]').length", 15);
    $page->script($center);
    $page->click($size.' [data-slot="select-trigger"]')
        ->assertScript($aligned)
        ->assertNoJavaScriptErrors();
});

it('searches by email', function () {
    visit('/data')
        ->type('[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'user07')
        ->assertScript(visibleEmails(), '07')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '1 total')
        ->assertQueryStringHas('search', 'user07');
});

it('sorts from a column header', function () {
    $name = USERS.' >> button[aria-label="Name — Sort"]';
    $email = USERS.' >> button[aria-label="Email — Sort"]';
    $options = '[data-slot="dropdown-menu-content"]:popover-open [data-data-table-sort-option=';

    visit('/data')
        ->assertSeeIn($name, 'Name')
        ->click($name)
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10')
        ->click($options.'"desc"]')
        ->assertScript(visibleEmails(), '15,14,13,12,11,10,09,08,07,06')
        ->assertQueryStringHas('sortBy', 'name')
        ->click($email)
        ->click($options.'"asc"]')
        ->assertScript("document.querySelector('button[aria-label=\"Name — Sort\"]').closest('th').hasAttribute('aria-sort')", false)
        ->assertQueryStringHas('sortBy', 'email')
        ->click($email)
        ->assertAttribute($options.'"asc"]', 'aria-checked', 'true')
        ->click($options.'"default"]')
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10');
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

it('updates selection counts across pages and runs toolbar actions on the caller', function () {
    $toolbar = USERS.' >> [data-slot="data-table-selection-toolbar"]';
    $count = $toolbar.' [data-slot="data-table-selection-count"]';

    visit('/data')->resize(1280, 900)
        ->assertMissing($toolbar)
        ->click(USERS.' >> [data-data-table-select-all]')
        ->assertSeeIn($count, '10 selected')
        ->assertScript("[...document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('[data-data-table-select-row]')].every(box => box.checked)")
        ->click(USERS.' >> [data-data-table-select-row] >> nth=0')
        ->assertSeeIn($count, '9 selected')
        ->click(USERS.' >> [data-slot="pagination"] button:text-is("2")')
        ->assertScript(visibleEmails(), '11,12,13,14,15')
        ->assertSeeIn($count, '9 selected')
        ->click(USERS.' >> [data-data-table-select-all]')
        ->assertSeeIn($count, '14 selected')
        ->click(USERS.' >> [data-data-table-select-all]')
        ->assertSeeIn($count, '9 selected')
        ->click(USERS.' >> [data-data-table-select-row] >> nth=0')
        ->assertSeeIn($count, '10 selected')
        ->click($toolbar.' button:text-is("Export")')
        ->assertSee('10 users would be exported')
        ->click('tr[data-row-id="marketing"] [data-data-table-select-row]')
        ->assertSeeIn('#teams', 'marketing')
        ->type('[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'user1')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '6 total')
        ->assertMissing($toolbar)
        ->assertScript("[...document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('[data-data-table-select-row]')].every(box => !box.checked)")
        ->assertSeeIn('#teams', 'marketing')
        ->click(USERS.' >> [data-data-table-select-row] >> nth=0')
        ->assertSeeIn($count, '1 selected')
        ->click($toolbar.' [aria-label="Clear selection"]')
        ->assertMissing($toolbar)
        ->assertNoJavaScriptErrors();
});

it('expands a row into its details', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-row-toggle] >> nth=0')
        ->assertSee('Email verified')
        ->assertAttribute(USERS.' >> [data-data-table-row-toggle] >> nth=0', 'aria-expanded', 'true')
        ->click(USERS.' >> [data-detail-for] button:text-is("Update detail note") >> nth=0')
        ->assertSee('Details reviewed')
        ->click(USERS.' >> button:text-is("View user") >> nth=0')
        ->assertSeeIn('[data-slot="toast"]', 'user01@example.com');
});

it('keeps independent selection toolbars fixed and reachable while scrolling', function (int $width) {
    $tree = '[data-slot="data-table"] >> nth=1';
    $position = <<<'JS'
        (() => {
            const bars = [...document.querySelectorAll('[data-slot="data-table-selection-toolbar"]')];
            const [first, second] = bars.map(bar => bar.getBoundingClientRect());
            return bars.length === 2
                && bars.every(bar => getComputedStyle(bar).position === 'fixed')
                && first.top - second.bottom >= 7
                && Math.abs(first.bottom - (innerHeight - 16)) < 1
                && [first, second].every(bar => bar.left >= 0 && bar.right <= innerWidth);
        })()
        JS;

    $page = visit('/data')->resize($width, 900)
        ->click(USERS.' >> [data-data-table-select-row] >> nth=0')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-selection-toolbar"]', '1 selected');

    $page->script("document.querySelectorAll('[data-data-table-select-all]')[1].scrollIntoView({ block: 'center' })");
    $page->click($tree.' >> [data-data-table-select-all]')
        ->assertScript($position)
        ->click($tree.' >> [data-data-table-select-row] >> nth=0')
        ->assertScript($position)
        ->click($tree.' >> [data-slot="data-table-selection-toolbar"] [aria-label="Clear selection"]')
        ->assertMissing($tree.' >> [data-slot="data-table-selection-toolbar"]')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-selection-toolbar"]', '1 selected')
        ->assertNoJavaScriptErrors();
})->with(['desktop' => [1280], 'mobile' => [390]]);

it('keeps the column settings top-aligned when toggling secondary filters', function (int $width) {
    User::query()->where('email', 'user01@example.com')->update(['email_verified_at' => null]);
    $toolbar = USERS.' >> [data-slot="data-table-toolbar"]';
    $advanced = $toolbar.' [data-slot="data-table-secondary-filters"]';
    $aligned = <<<'JS'
        (() => {
            const table = document.querySelector('eso-data-table');
            const toolbar = table.querySelector('[data-slot="data-table-toolbar"]').getBoundingClientRect();
            const settings = table.querySelector('[data-data-table-view-options]').getBoundingClientRect();
            return Math.abs(toolbar.top - settings.top) < 1 && settings.left >= toolbar.right;
        })()
        JS;

    visit('/data')->resize($width, 900)
        ->assertScript($aligned)
        ->click($toolbar.' button:text-is("More filters")')
        ->assertScript($aligned)
        ->click($advanced.' input[type="checkbox"]')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '14 total')
        ->click($toolbar.' button:text-is("More filters")')
        ->assertScript($aligned)
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '14 total')
        ->click($toolbar.' [data-data-table-reset]')
        ->assertSeeIn(USERS.' >> [data-slot="data-table-count"]', '15 total');
})->with(['desktop' => [1280], 'mobile' => [390]]);

it('hides a column from the view options', function () {
    visit('/data')
        ->click(USERS.' >> [data-data-table-view-options]')
        ->click('[data-slot="dropdown-menu-content"]:popover-open [data-slot="dropdown-menu-checkbox-item"]:has-text("Email")')
        ->assertScript("document.querySelectorAll('[data-slot=\"data-table\"]')[0].querySelectorAll('.lowercase').length", 0)
        ->refresh()
        ->assertScript(visibleEmails(), '01,02,03,04,05,06,07,08,09,10');
});

it('reorders table columns through the settings list while leaving the fixed column in place', function () {
    $settings = '[data-slot="dropdown-menu-content"]:popover-open';
    $email = $settings.' [wire\\:sort\\:item="email"] [data-slot="data-table-column-handle"]';
    $joined = $settings.' [wire\\:sort\\:item="created_at"] [data-slot="data-table-column-handle"]';
    $order = <<<'JS'
        (() => {
            const table = document.querySelector('eso-data-table table');
            const headings = [...table.querySelectorAll('th [data-data-table-sort]')].map(button => button.getAttribute('aria-label'));
            return headings[0] === 'Name — Sort' && headings.indexOf('Email — Sort') > headings.indexOf('Role — Sort');
        })()
        JS;

    visit('/data')
        ->click(USERS.' >> [data-data-table-view-options]')
        ->assertMissing($settings.' [wire\\:sort\\:item="name"]')
        ->drag($email, $joined)
        ->assertScript($order);
});

it('expands tree rows and binds their selection', function () {
    visit('/data')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform\"]').hidden", false)
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"]').hidden")
        ->click('tr[data-row-id="engineering/platform"] [data-data-table-row-toggle="detail"]')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"]').hidden")
        ->click('tr[data-row-id="engineering/platform"] [data-data-table-row-toggle="tree"]')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"]').hidden", false)
        ->click('tr[data-row-id="engineering/platform/api"] [data-data-table-select-row]')
        ->click('tr[data-row-id="engineering/platform"] [data-data-table-row-toggle="tree"]')
        ->assertSeeIn('#teams', 'engineering/platform/api')
        ->click('tr[data-row-id="marketing"] [data-data-table-select-row]')
        ->assertSeeIn('#teams', 'marketing');
});

it('selects only visible tree rows without touching the users table', function () {
    $tree = '[data-slot="data-table"] >> nth=1';
    $toolbar = $tree.' >> [data-slot="data-table-selection-toolbar"]';

    visit('/data')->resize(1280, 900)
        ->click($tree.' >> [data-data-table-select-all]')
        ->assertScript("document.querySelector('#teams').textContent.trim().split(', ').length", 8)
        ->assertSeeIn($toolbar, '8 selected')
        ->assertMissing(USERS.' >> [data-slot="data-table-selection-toolbar"]')
        ->assertScript("document.querySelector('tr[data-row-id=\"engineering/platform/api\"] input').checked", false)
        ->click($tree.' >> tr[data-row-id="marketing"] [data-data-table-select-row]')
        ->assertSeeIn($toolbar, '7 selected')
        ->click($toolbar.' [aria-label="Clear selection"]')
        ->assertSeeIn('#teams', 'none')
        ->assertMissing($toolbar)
        ->assertNoJavaScriptErrors();
});
