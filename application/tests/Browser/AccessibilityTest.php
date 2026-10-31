<?php

use App\Models\User;

/*
 * axe finds no critical or serious accessibility issues on any demo page. Color contrast
 * is left out: the colors are shadcn's own, which an app changes in its @theme.
 */

beforeEach(function () {
    User::factory()->count(12)->create();
});

it('has no serious accessibility issues', function (string $path) {
    $page = visit($path);

    $page->page()->waitForLoadState('networkidle');

    $violations = $page->script(<<<'JS'
        (async () => (await axe.run({ rules: { 'color-contrast': { enabled: false } } })).violations
            .filter(violation => ['critical', 'serious'].includes(violation.impact))
            .map(violation => `${violation.id}: ${violation.nodes.map(node => node.target.join(' ')).join(', ')}`))()
        JS);

    expect($violations)->toBe([]);
})->with(fn () => ['/', ...collect(require __DIR__.'/../../config/demo.php')->collapse()->keys()->map(fn (string $page) => "/{$page}")]);
