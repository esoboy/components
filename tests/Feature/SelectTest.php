<?php

use Esoboy\Ui\Livewire\Select;
use Esoboy\Ui\Support\Options;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Livewire\Livewire;

it('uses internal state and preserves typed choices', function () {
    Livewire::test(Select::class, ['options' => [['value' => 0, 'label' => 'Zero'], ['value' => '1', 'label' => 'One']], 'initialValue' => 0])
        ->assertSet('value', 0)->call('choose', '1')->assertSet('value', '1')
        ->call('choose', 'unknown')->assertSet('value', '1');
});

it('displays a zero label instead of treating it as an empty selection', function () {
    Livewire::test(Select::class, ['options' => [['value' => 0, 'label' => '0']], 'initialValue' => 0])
        ->assertSee('>0</span>', false);
});

it('requests controlled changes without mutating the external value', function () {
    Livewire::test(Select::class, ['mode' => 'controlled', 'externalValue' => 'a', 'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]])
        ->call('choose', 'b')->assertSet('externalValue', 'a')->assertSet('value', null)
        ->assertDispatched('value-changed', value: 'b');
});

it('rejects disabled and readonly changes', function (string $state) {
    Livewire::test(Select::class, [$state => true, 'initialValue' => 'a', 'options' => [['value' => 'b', 'label' => 'B']]])
        ->call('choose', 'b')->assertSet('value', 'a')->assertNotDispatched('value-changed');
})->with(['disabled', 'readonly']);

it('renders through the only public naming convention', function () {
    $html = Blade::render('<x-ui::select id="test" :initial-value="0" :options="[[\'value\' => 0, \'label\' => \'Zero\']]" />');
    expect($html)->toContain('Zero')->toContain('id="test"')->toContain('aria-selected="true"');
});

it('distinguishes explicit empty values and rejects conflicting sources', function () {
    foreach ([null, 0, ''] as $value) {
        $html = Blade::render('<x-ui::select id="explicit" :value="$value" />', compact('value'));
        expect($html)->toContain('&quot;mode&quot;:&quot;controlled&quot;');
    }
    expect(fn () => Blade::render('<x-ui::select id="conflict" :value="null" :initial-value="0" />'))->toThrow(ViewException::class);
});

it('treats a null option as clearing a single selection and preserves empty string values', function () {
    $options = [
        ['value' => null, 'label' => 'Choose'],
        ['value' => '', 'label' => 'Empty string'],
        ['value' => 'a', 'label' => 'Apple', 'group' => 'Fruit', 'separatorBefore' => true],
    ];
    Livewire::test(Select::class, ['options' => $options, 'initialValue' => 'a'])
        ->call('choose', null)->assertSet('value', null)
        ->assertDispatched('value-changed', value: null)
        ->call('choose', '')->assertSet('value', '')->assertSee('Empty string');
    Livewire::test(Select::class, ['options' => $options, 'mode' => 'controlled', 'externalValue' => 'a'])
        ->call('choose', null)->assertSet('externalValue', 'a')
        ->assertDispatched('value-changed', value: null);
});

it('validates popup placement on the public component', function () {
    expect(fn () => Blade::render('<x-ui::select id="invalid-side" side="diagonal" />'))->toThrow(ViewException::class);
    expect(fn () => Blade::render('<x-ui::select id="invalid-align" align="middle" />'))->toThrow(ViewException::class);
    expect(Blade::render('<x-ui::select id="valid-placement" side="inline-start" align="end" :align-item-with-trigger="false" />'))
        ->toContain('data-preferred-side="inline-start"')->toContain('data-align="end"');
});

it('renders structured option descriptions as escaped Blade text fragments', function () {
    $description = ['Europe', ' (', '<al>', ')'];
    $options = [['value' => 'al', 'label' => 'Albania', 'description' => $description]];
    Livewire::test(Select::class, ['options' => $options, 'initialValue' => 'al'])
        ->assertSee('Europe')->assertSee('&lt;al&gt;', false)->assertDontSee('<al>', false)
        ->call('choose', 'al')->assertSet('value', 'al');
    expect(fn () => Options::validate([['value' => 'a', 'label' => 'A', 'description' => ['Text', ['invalid']]]]))
        ->toThrow(InvalidArgumentException::class);
});
