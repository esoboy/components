<?php

use Esoboy\Ui\Livewire\Questionnaire;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Livewire\Livewire;

function questionnaireItems(): array
{
    return [
        ['name' => 'single', 'prompt' => 'Single', 'required' => true, 'choices' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B', 'disabled' => true]], 'input' => ['label' => 'Other']],
        ['name' => 'multi', 'prompt' => 'Multiple', 'multiple' => true, 'choices' => [['value' => 'x', 'label' => 'X'], ['value' => 'y', 'label' => 'Y']], 'input' => ['label' => 'More']],
        ['name' => 'omitted', 'prompt' => 'Omitted', 'disabled' => true],
        ['name' => 'text', 'prompt' => 'Text', 'required' => true, 'input' => ['label' => 'Text']],
    ];
}

it('validates before advancing and submits only answered or explicitly skipped enabled items', function () {
    $test = Livewire::test(Questionnaire::class, ['items' => questionnaireItems()]);
    $test->call('next')->assertSet('invalid', 'single')->call('choose', 'b')->assertSet('value', null)
        ->call('choose', 'a')->call('next')->assertSet('activeItem', 'multi')
        ->call('next')->assertSet('invalid', 'multi')->call('skip')->assertSet('activeItem', 'text')
        ->call('submit')->assertNotDispatched('questionnaire-submitted')->call('writeText', ' Done ')->call('submit')
        ->assertDispatched('questionnaire-submitted', answers: [
            'single' => ['choices' => ['a'], 'text' => '', 'status' => 'answered'],
            'multi' => ['choices' => [], 'text' => '', 'status' => 'skipped'],
            'text' => ['choices' => [], 'text' => 'Done', 'status' => 'answered'],
        ]);
});

it('keeps freeform answers exclusive for single choice and additive for multiple choice', function () {
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems()])->call('choose', 'a')->call('writeText', 'Custom')
        ->assertSet('answers.single.choices', [])->assertSet('answers.single.text', 'Custom')
        ->call('choose', 'a')->assertSet('answers.single.text', '')->call('next')->call('choose', 'x')->call('choose', 'y')
        ->call('writeText', 'More')->assertSet('answers.multi.choices', ['x', 'y'])->assertSet('answers.multi.text', 'More')
        ->call('choose', 'x')->assertSet('answers.multi.choices', ['y']);
});

it('restores a draft and resets navigation and answers', function () {
    $initial = ['single' => ['choices' => ['a']], 'multi' => ['choices' => ['x']]];
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), 'initialValue' => $initial, 'initialItem' => 'multi'])
        ->assertSet('position', 1)->call('choose', 'y')->call('next')->assertSet('activeItem', 'text')
        ->call('resetAnswers')->assertSet('value', $initial)->assertSet('position', 1)->assertSet('invalid', null);
});

it('does not mutate readonly or disabled answers and respects controlled ownership', function () {
    foreach (['readonly', 'disabled'] as $flag) {
        Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), $flag => true])
            ->call('choose', 'a')->call('writeText', 'Change')->call('skip')->call('resetAnswers')->call('submit')
            ->assertSet('value', null)->assertNotDispatched('questionnaire-submitted');
    }
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), 'mode' => 'controlled', 'externalValue' => []])
        ->call('choose', 'a')->assertSet('externalValue', [])->assertSet('value', null)->assertDispatched('value-changed')
        ->call('next')->assertSet('position', 0)->assertSet('invalid', 'single');
});

it('supports controlled navigation and validates all items on final submit', function () {
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), 'initialValue' => ['single' => ['choices' => ['a']]], 'item' => 'single'])
        ->call('next')->assertSet('position', 0)->assertDispatched('item-changed', item: 'multi');
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), 'initialItem' => 'text'])
        ->call('writeText', 'Last')->call('submit')->assertSet('activeItem', 'single')->assertSet('invalid', 'single')
        ->assertNotDispatched('questionnaire-submitted');
});

it('filters disabled or unknown answers and rejects duplicate question identifiers', function () {
    Livewire::test(Questionnaire::class, ['items' => questionnaireItems(), 'initialValue' => ['single' => ['choices' => ['b', 'missing']]]])
        ->assertSet('answers.single.status', 'unanswered')->call('skip')->assertSet('position', 0);
    expect(fn () => Livewire::test(Questionnaire::class, ['items' => [['name' => 'same', 'prompt' => 'One'], ['name' => 'same', 'prompt' => 'Two']]]))
        ->toThrow(ViewException::class);
});

it('validates toolbar presets at the public editor state boundary', function () {
    expect(Blade::render('<x-ui::editor id="preset" toolbar-preset="basic" />'))->toContain('toolbar-preset="basic"');
    expect(fn () => Blade::render('<x-ui::editor id="preset" toolbar-preset="unknown" />'))->toThrow(ViewException::class);
});
