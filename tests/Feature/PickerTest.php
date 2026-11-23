<?php

use Esoboy\Ui\Livewire\Cascader;
use Esoboy\Ui\Livewire\DatePicker;
use Esoboy\Ui\Livewire\TimePicker;
use Esoboy\Ui\Livewire\Tree;
use Esoboy\Ui\Livewire\TreeSelect;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('requires explicit parent selection and preserves typed single and multiple values', function (string $component) {
    $nodes = [['value' => 0, 'label' => 'East', 'children' => [['value' => '01', 'label' => 'Coast']]]];
    Livewire::test($component, ['nodes' => $nodes])
        ->call('choose', 0)->assertSet('value', null)->assertNotDispatched('value-changed')
        ->call('expand', 0)->assertSet('value', null)->call('choose', '01')->assertSetStrict('value', '01');
    Livewire::test($component, ['nodes' => $nodes, 'selectParents' => true])
        ->call('choose', 0)->assertSetStrict('value', 0)
        ->call('expand', 0)->assertSetStrict('value', 0)->call('choose', '01')->assertSetStrict('value', '01');
    Livewire::test($component, ['nodes' => $nodes, 'multiple' => true, 'selectParents' => true])
        ->call('choose', 0)->call('choose', '01')->assertSetStrict('value', [0, '01'])
        ->call('choose', 0)->assertSetStrict('value', ['01'])->call('choose', '01')->assertSet('value', []);
    foreach (['disabled', 'readonly'] as $guard) {
        Livewire::test($component, ['nodes' => $nodes, 'selectParents' => true, $guard => true])
            ->call('choose', 0)->assertSet('value', null)->assertNotDispatched('value-changed');
    }
    $nodes[0]['disabled'] = true;
    Livewire::test($component, ['nodes' => $nodes, 'selectParents' => true])
        ->call('choose', 0)->assertSet('value', null)->assertNotDispatched('value-changed');
})->with([Cascader::class, Tree::class, TreeSelect::class]);

it('edits individual time units and clears to null', function () {
    Livewire::test(TimePicker::class, ['seconds' => true, 'clearable' => true, 'initialValue' => '09:31:58'])
        ->call('chooseTime', 'hour', 0)->assertSet('value', '00:31:58')
        ->call('chooseTime', 'minute', 5)->assertSet('value', '00:05:58')
        ->call('chooseTime', 'second', 60)->assertSet('value', '00:05:58')
        ->call('chooseTime', 'second', 0)->assertSet('value', '00:05:00')
        ->call('clear')->assertSet('value', null)->assertDispatched('value-changed', value: null);
    Livewire::test(TimePicker::class)->call('chooseTime', 'second', 15)->assertSet('value', null)
        ->call('chooseTime', 'minute', 8)->assertSet('value', '00:08')
        ->call('clear')->assertSet('value', '00:08');
});

it('gives unbound pickers on a plain Blade page independent popup content', function () {
    $html = Blade::render('<x-ui::date-picker /><x-ui::date-picker /><x-ui::time-picker /><x-ui::time-picker><x-slot:extra></x-slot:extra></x-ui::time-picker><x-ui::datetime-picker />');
    preg_match_all('/x-teleport="#(ui-picker-[a-f0-9]+)"/', $html, $targets);
    expect(array_unique($targets[1]))->toHaveCount(5);
    expect($html)->not->toContain('class="ui-picker-extra"');
    foreach ($targets[1] as $target) {
        expect($html)->toContain('id="'.$target.'"');
    }
});

it('keeps datetime as one local value and respects calendar exclusions', function () {
    Livewire::test(DatePicker::class, ['withTime' => true, 'seconds' => true, 'clearable' => true, 'disabledDates' => ['2026-11-20']])
        ->call('chooseTime', 'hour', 15)->assertSet('value', null)
        ->call('choose', '2026-11-18')->assertSet('value', '2026-11-18T00:00:00')
        ->call('chooseTime', 'hour', 23)->call('chooseTime', 'second', 59)
        ->call('choose', '2026-11-19')->assertSet('value', '2026-11-19T23:00:59')
        ->call('choose', '2026-11-20')->assertSet('value', '2026-11-19T23:00:59')
        ->call('clear')->assertSet('value', null)->assertSet('selected', []);
});

it('guards changes in readonly and disabled time and datetime pickers', function (string $flag) {
    Livewire::test(TimePicker::class, [$flag => true, 'clearable' => true, 'initialValue' => '00:00'])
        ->call('chooseTime', 'minute', 5)->call('clear')->assertSet('value', '00:00')->assertNotDispatched('value-changed');
    Livewire::test(DatePicker::class, [$flag => true, 'withTime' => true, 'clearable' => true, 'initialValue' => '2026-11-17T00:00'])
        ->call('choose', '2026-11-18')->call('chooseTime', 'hour', 9)->call('clear')
        ->assertSet('value', '2026-11-17T00:00')->assertNotDispatched('value-changed');
})->with(['readonly', 'disabled']);

it('rejects invalid clock values and mismatched precision', function (string $time, bool $seconds) {
    expect(fn () => Livewire::test(TimePicker::class, ['initialValue' => $time, 'seconds' => $seconds]))
        ->toThrow($seconds ? 'Time must use HH:mm:ss.' : 'Time must use HH:mm.');
})->with([['24:15', false], ['13:65', false], ['09:00:70', true], ['09:00', true], ['09:00:01', false]]);

it('clears date collections and requests controlled changes without overwriting the parent', function () {
    foreach (['range', 'multiple'] as $selection) {
        Livewire::test(DatePicker::class, [$selection => true, 'clearable' => true, 'initialValue' => ['2026-11-17']])
            ->call('clear')->assertSet('value', [])->assertDispatched('value-changed', value: []);
    }
    Livewire::test(TimePicker::class, ['mode' => 'controlled', 'externalValue' => '09:30', 'clearable' => true])
        ->call('chooseTime', 'hour', 14)->assertSet('current', '09:30')->assertDispatched('value-changed', value: '14:30')
        ->call('clear')->assertSet('current', '09:30')->assertDispatched('value-changed', value: null);
});
