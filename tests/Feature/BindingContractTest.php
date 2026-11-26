<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Livewire\NumberInput;
use Esoboy\Ui\Livewire\Select;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('renders explicit tab references and the controlled selection before hydration', function () {
    $template = <<<'BLADE'
        <x-ui::tabs :value="$selection">
            <x-ui::tabs.content id="panel-b" value="b">Second panel</x-ui::tabs.content>
            <x-ui::tabs.list>
                <x-ui::tabs.tab id="tab-a" value="a">A</x-ui::tabs.tab>
                <x-ui::tabs.tab id="tab-b" value="b">B</x-ui::tabs.tab>
            </x-ui::tabs.list>
            <x-ui::tabs.content id="panel-a" value="a">First panel</x-ui::tabs.content>
        </x-ui::tabs>
        BLADE;
    foreach (['b', null] as $selection) {
        $dom = HTMLDocument::createFromString(Blade::render($template, compact('selection')), LIBXML_NOERROR);
        expect($dom->getElementById('tab-a')->getAttribute('aria-controls'))->toBe('panel-a')
            ->and($dom->getElementById('panel-b')->getAttribute('aria-labelledby'))->toBe('tab-b')
            ->and($dom->getElementById('tab-a')->getAttribute('aria-selected'))->toBe('false')
            ->and($dom->getElementById('panel-b')->hasAttribute('hidden'))->toBe($selection === null);
    }
});

it('places public keys on the outer DOM element of native and menu controls', function (string $component) {
    $html = Blade::render('<main><x-ui::'.$component.' wire:key="record" /></main>');
    $root = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('main')->firstElementChild;
    expect($root->getAttribute('wire:key'))->toBe('record')
        ->and($root->querySelector('[wire\\:key="record"]'))->toBeNull();
})->with(['color-picker', 'native-select', 'password-input', 'switch', 'table', 'toggle', 'toggle-group.item', 'dropdown-menu.checkbox-item', 'dropdown-menu.radio-item']);

it('scopes anonymous table rows independently and keys the grouped option decorations', function () {
    $document = HTMLDocument::createFromString(Blade::render(<<<'BLADE'
        <x-ui::data-table :rows="$rows" :columns="$columns" />
        <x-ui::data-table :rows="$rows" :columns="$columns" />
        <x-ui::select :options="[['value'=>'a', 'label'=>'A', 'group'=>'First', 'separatorAfter'=>true], ['value'=>'b', 'label'=>'B', 'group'=>'Second']]" />
        BLADE, ['rows' => [['id' => 'same']], 'columns' => ['id' => ['label' => 'ID']]]), LIBXML_NOERROR);
    $rows = $document->querySelectorAll('tbody tr');
    expect($rows->length)->toBe(2)
        ->and($rows->item(0)->getAttribute('wire:key'))->not->toBe($rows->item(1)->getAttribute('wire:key'));
    $decorations = $document->querySelectorAll('.ui-option-group-label, .ui-option-separator');
    $keys = array_map(fn ($element) => $element->getAttribute('wire:key'), iterator_to_array($decorations));
    expect($decorations->length)->toBe(3)->and(array_unique(array_filter($keys)))->toHaveCount(3);
});

it('only initializes the clipboard behavior on a rendered copy button', function () {
    $document = HTMLDocument::createFromString(Blade::render(<<<'BLADE'
        <x-ui::code-block>Plain slot</x-ui::code-block>
        <x-ui::code-block code="example"><x-slot:header>Custom header</x-slot:header></x-ui::code-block>
        <x-ui::code-block code="example" />
        BLADE), LIBXML_NOERROR);
    $adapters = $document->querySelectorAll('[x-data="uiCode"]');
    expect($adapters->length)->toBe(1)->and(strtolower($adapters->item(0)->tagName))->toBe('button');
});

it('does not accept a disabled optimistic choice and repeated selection intents are idempotent', function () {
    $component = Livewire::test(Select::class, [
        'multiple' => true,
        'initialValue' => ['a'],
        'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B'], ['value' => 'c', 'label' => 'C', 'disabled' => true]],
    ]);
    $component->call('confirmChoice', 'b', true)->call('confirmChoice', 'b', true)
        ->call('confirmChoice', 'c', true)->assertSet('value', ['a', 'b'])
        ->call('confirmChoice', 'a', false)->call('confirmChoice', 'a', false)->assertSet('value', ['b']);
});

it('places native validation and descriptive attributes on the public form controls', function () {
    $dom = HTMLDocument::createFromString(Blade::render(<<<'BLADE'
        <x-ui::mentions required minlength="2" maxlength="20" placeholder="Mention" form="external" aria-describedby="help" />
        <x-ui::radio-group name="answer" required><x-ui::radio-group.item value="yes" /></x-ui::radio-group>
        <x-ui::tags-input name="tags" required placeholder="New tag" />
        BLADE), LIBXML_NOERROR);
    $input = $dom->querySelector('textarea');
    expect($input->hasAttribute('required'))->toBeTrue()
        ->and($input->getAttribute('maxlength'))->toBe('20')
        ->and($input->getAttribute('placeholder'))->toBe('Mention')
        ->and($input->getAttribute('aria-describedby'))->toBe('help')
        ->and($dom->querySelector('input[type=radio]')->hasAttribute('required'))->toBeTrue()
        ->and($dom->querySelector('input[placeholder="New tag"]'))->not->toBeNull()
        ->and($dom->querySelector('select[required]'))->not->toBeNull();
});

it('validates controlled number proposals on the server and preserves the external value', function () {
    $parameters = [
        'mode' => 'controlled', 'externalValue' => 5,
        'settings' => ['min' => 1, 'max' => 10, 'step' => 1, 'size' => 'default'],
    ];
    $number = Livewire::test(NumberInput::class, $parameters);
    $number->call('propose', '7.5')->assertDispatched('value-changed', value: 7.5)->assertSet('externalValue', 5)
        ->call('propose', '')->assertDispatched('value-changed', value: null)
        ->call('propose', '40')->assertNotDispatched('value-changed')
        ->call('propose', 'not numeric')->assertNotDispatched('value-changed');
    Livewire::test(NumberInput::class, [...$parameters, 'readonly' => true])
        ->call('propose', 8)->assertNotDispatched('value-changed');
});

it('keeps numeric and string keys distinct when browsing tree branches', function (string $component) {
    $nodes = [
        ['value' => 0, 'label' => 'Integer', 'children' => [['value' => 1, 'label' => 'Integer child']]],
        ['value' => '0', 'label' => 'Text', 'children' => [['value' => '1', 'label' => 'Text child']]],
    ];
    $control = Livewire::test('ui::'.$component, ['nodes' => $nodes]);
    $control->call('expand', 0)->assertSee('Integer child')->assertDontSee('Text child')
        ->call('choose', 1)->assertSet('value', 1)
        ->call('expand', '0')->call('choose', '1')->assertSet('value', '1');
})->with(['tree', 'tree-select', 'cascader']);

it('distinguishes a bare mention, a filtered match and a missing member', function () {
    $mentions = Livewire::test('ui::mentions', ['options' => [['value' => 'a', 'label' => 'Alice'], ['value' => 'b', 'label' => 'Beta']]]);
    $mentions->set('value', 'Hello')->assertDontSee('class="ui-mentions-suggestions"', false)
        ->set('value', '@')->assertSee('Alice')->assertSee('Beta')
        ->set('value', '@be')->assertSee('Beta');
    expect(array_column($mentions->instance()->suggestions, 'value'))->toBe(['b']);
    $mentions->set('value', '@unknown')->assertSee(__('ui::ui.empty_states.member_matches.title'));
});

it('restores the mounting form value for composite controls', function (string $component, mixed $initial, mixed $edited, array $options = []) {
    Livewire::test('ui::'.$component, ['initialValue' => $initial, ...$options])
        ->set('value', $edited)->call('resetFormValue')->assertSet('value', $initial)
        ->assertDispatched('value-changed', value: $initial);
})->with([
    ['calendar', '2027-06-05', '2027-06-15'],
    ['date-picker', '2027-06-05', '2027-06-15'],
    ['date-picker', '2027-06-05T09:10', '2027-06-15T12:30', ['withTime' => true]],
    ['time-picker', '09:10', '12:30'],
    ['combobox', 'a', 'b'],
    ['icon-select', 'star', 'zoom-out'],
    ['cascader', 'a', 'b'],
    ['tree', 'a', 'b'],
    ['tree-select', 'a', 'b'],
    ['transfer', ['a'], ['b']],
    ['command', 'a', 'b'],
    ['tags-input', ['first'], ['second']],
    ['mentions', 'Hello', 'Hello Alice'],
]);
