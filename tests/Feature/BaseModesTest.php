<?php

use Esoboy\Ui\Livewire\Calendar;
use Livewire\Livewire;

it('selects ordered date ranges and rejects disabled dates inside a range', function () {
    Livewire::test(Calendar::class, ['range' => true, 'numberOfMonths' => 2, 'initialValue' => []])
        ->call('choose', '2026-11-20')->assertSet('value', ['2026-11-20'])
        ->call('choose', '2026-11-17')->assertSet('value', ['2026-11-17', '2026-11-20'])
        ->assertSee('data-range="middle"', false)
        ->call('choose', '2026-12-01')->assertSet('value', ['2026-12-01']);
    Livewire::test(Calendar::class, ['range' => true, 'initialValue' => ['2026-11-17'], 'disabledDates' => ['2026-11-19']])
        ->call('choose', '2026-11-20')->assertSet('value', ['2026-11-17']);
    Livewire::test(Calendar::class, ['range' => true, 'initialValue' => ['2026-11-17']])
        ->call('choose', '2026-11-17')->assertSet('value', ['2026-11-17', '2026-11-17']);
});
