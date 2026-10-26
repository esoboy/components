<?php

use Esoboy\Ui\Support\Overlay;
use Esoboy\Ui\Tests\Fixtures\Menus;
use Livewire\Livewire;

it('pairs triggers and contents first in, last out', function () {
    $outer = Overlay::open('popover');
    $inner = Overlay::open('popover');

    expect(Overlay::close('popover'))->toBe($inner)
        ->and(Overlay::close('popover'))->toBe($outer);
});

it('finds the innermost open id of a kind', function () {
    $menu = Overlay::open('dropdown-menu');
    Overlay::open('dropdown-menu-sub');

    expect(Overlay::current('dropdown-menu'))->toBe($menu);
});

it('numbers ids per page outside Livewire', function () {
    expect(Overlay::open('popover'))->toBe('popover-page-1')
        ->and(Overlay::open('tooltip'))->toBe('tooltip-page-2');
});

it('keeps the same ids when a Livewire component re-renders', function () {
    $component = Livewire::test(Menus::class);
    $first = idsOf($component->html(), 'id');

    $component->call('$refresh');
    $second = idsOf($component->html(), 'id');

    expect($first)->toHaveCount(2)
        ->and($second)->toBe($first)
        ->and($first[0])->toContain($component->id());
});
