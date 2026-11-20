<?php

use Esoboy\Ui\Livewire\Cascader;
use Esoboy\Ui\Livewire\Combobox;
use Esoboy\Ui\Livewire\Command;
use Esoboy\Ui\Livewire\Transfer;
use Esoboy\Ui\Livewire\Tree;
use Esoboy\Ui\Livewire\TreeSelect;
use Livewire\Livewire;

function navigationNodes(): array
{
    return [
        ['value' => 'a', 'label' => 'A', 'children' => [['value' => 'a1', 'label' => 'A1']]],
        ['value' => 'b', 'label' => 'B', 'children' => [['value' => 'b1', 'label' => 'B1']]],
    ];
}

it('initializes cascader navigation from the selected leaf and retains browsing until selection changes', function () {
    Livewire::test(Cascader::class, ['nodes' => navigationNodes(), 'initialValue' => 'a1'])
        ->assertSet('path', ['a', 'a1'])->call('expand', 'b')->assertSet('path', ['b'])
        ->call('$refresh')->assertSet('path', ['b'])->set('value', 'b1')->assertSet('path', ['b', 'b1'])
        ->set('value', null)->assertSet('path', []);
});

it('does not carry unused inherited state', function () {
    foreach (['size', 'displayValue'] as $key) {
        expect(property_exists(Combobox::class, $key))->toBeFalse();
    }
    foreach (['size', 'displayValue', 'side', 'align', 'sideOffset', 'alignOffset', 'alignItemWithTrigger', 'popupClass'] as $key) {
        expect(property_exists(Command::class, $key))->toBeFalse();
    }
    foreach ([Tree::class, TreeSelect::class, Cascader::class] as $class) {
        expect(property_exists($class, 'options'))->toBeFalse();
    }
    expect(property_exists(Cascader::class, 'expanded'))->toBeFalse()
        ->and(property_exists(Tree::class, 'placeholder'))->toBeFalse()
        ->and(property_exists(Transfer::class, 'multiple'))->toBeFalse()
        ->and(property_exists(Transfer::class, 'placeholder'))->toBeFalse();
});

it('keeps a checked parent independent from the browsed cascader column', function () {
    Livewire::test(Cascader::class, ['nodes' => navigationNodes(), 'selectParents' => true, 'multiple' => true])
        ->call('choose', 'a')->assertSet('path', [])->assertSet('value', ['a'])
        ->call('expand', 'b')->call('choose', 'b1')->assertSet('path', ['b', 'b1'])
        ->call('choose', 'a')->assertSet('value', ['b1'])->assertSet('path', ['b', 'b1'])
        ->call('choose', 'a')->call('$refresh')->assertSet('path', ['b', 'b1'])
        ->set('value', ['a1'])->assertSet('path', ['a', 'a1']);
});

it('locates numeric zero and multiple selections without treating zero as an empty value', function () {
    $nodes = [['value' => 'parent', 'label' => 'Parent', 'children' => [['value' => 0, 'label' => 'Zero']]]];
    Livewire::test(Cascader::class, ['nodes' => $nodes, 'initialValue' => 0])
        ->assertSet('path', ['parent', 0])->assertSee('Zero');
    Livewire::test(Cascader::class, ['nodes' => $nodes, 'initialValue' => '0'])
        ->assertSet('path', []);
    Livewire::test(Cascader::class, ['nodes' => navigationNodes(), 'multiple' => true, 'initialValue' => ['a1', 'b1']])
        ->assertSet('path', ['b', 'b1']);
});

it('does not navigate or select through a disabled ancestor', function () {
    $nodes = [['value' => 'parent', 'label' => 'Parent', 'disabled' => true, 'children' => [['value' => 'child', 'label' => 'Child']]]];
    Livewire::test(Cascader::class, ['nodes' => $nodes, 'initialValue' => null])
        ->call('choose', 'child')->assertSet('value', null)->assertSet('path', []);
});
