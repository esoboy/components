<?php

use Esoboy\Ui\Livewire\Combobox;
use Esoboy\Ui\Livewire\Command;
use Esoboy\Ui\Livewire\Mentions;
use Esoboy\Ui\Livewire\Transfer;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('renders public empty text at the right size and escapes application copy', function (string $tag, string $props, string $size, int $count) {
    $title = 'No <teams>';
    $description = 'Try A & B.';
    $html = Blade::render('<x-ui::'.$tag.' '.$props.' :empty-title="$title" :empty-description="$description" />', compact('title', 'description'));
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $states = $xpath->query('//*[@data-slot="empty"]');

    expect($states->length)->toBe($count);
    foreach ($states as $state) {
        expect($state->getAttribute('data-size'))->toBe($size)
            ->and($xpath->evaluate('string(.//*[@data-slot="empty-title"])', $state))->toBe($title)
            ->and($xpath->evaluate('string(.//*[@data-slot="empty-description"])', $state))->toBe($description)
            ->and($xpath->query('.//*[@data-slot="empty-media"]', $state)->length)->toBe($size === 'default' ? 1 : 0);
    }
})->with([
    'select' => ['select', '', 'sm', 1],
    'page size' => ['data-table.page-size', ':options="[]"', 'sm', 1],
    'combobox' => ['combobox', '', 'sm', 1],
    'command' => ['command', '', 'sm', 1],
    'cascader' => ['cascader', '', 'sm', 1],
    'tree select' => ['tree-select', '', 'sm', 1],
    'active mentions' => ['mentions', 'initial-value="@"', 'sm', 1],
    'transfer panels' => ['transfer', '', 'sm', 2],
    'steps' => ['steps', ':items="[]" :current="2"', 'sm', 1],
    'tree' => ['tree', '', 'default', 1],
    'questionnaire' => ['questionnaire', ':items="[[\'name\' => \'q\', \'prompt\' => \'Draft\', \'disabled\' => true]]"', 'default', 1],
]);

it('keeps placeholders, disabled options and ordinary text distinct from empty results', function () {
    $options = [['value' => 'only', 'label' => 'Only choice', 'disabled' => true]];
    expect(Blade::render('<x-ui::select :options="$options" placeholder="Pick a role" />', compact('options')))
        ->toContain('Only choice', 'Pick a role')->not->toContain('data-slot="empty"');
    expect(Blade::render('<x-ui::native-select />'))->toContain('<select')->not->toContain('data-slot="empty"');
    Livewire::test(Mentions::class, ['options' => $options])
        ->assertDontSeeHtml('data-slot="empty"')->set('value', '@missing')->assertSee('No matching members')
        ->set('value', '@only')->assertDontSeeHtml('data-slot="empty"')->assertSee('Only choice')
        ->set('value', 'Send an update')->assertDontSeeHtml('data-slot="mentions-suggestions"');
    Livewire::test(Mentions::class, ['initialValue' => '@'])->assertSee('No members to mention');
});

it('distinguishes empty sources and active searches in both locales', function (string $component, string $sourceTitle) {
    app()->setLocale('cn');
    Livewire::test($component)->assertSee($sourceTitle)->assertDontSeeHtml('data-slot="empty-description"');
    $options = [['value' => 0, 'label' => 'Alpha']];
    Livewire::test($component, ['options' => $options])->assertDontSeeHtml('data-slot="empty"')
        ->set('query', 'missing')->assertSee('没有匹配结果')->assertSee('试试其他关键词。')
        ->set('query', 'alp')->assertSee('Alpha')->assertDontSeeHtml('data-slot="empty"');
    app()->setLocale('en');
    Livewire::test($component, ['options' => $options, 'emptyDescription' => ''])
        ->set('query', 'missing')->assertSee('No matching results')->assertDontSeeHtml('data-slot="empty-description"');
})->with([[Combobox::class, '暂无可选项'], [Command::class, '暂无可用命令']]);

it('distinguishes available, selected and filtered transfer panels', function () {
    Livewire::test(Transfer::class)->assertSee('No options available')->assertSee('No items selected');
    $options = [['value' => 0, 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']];
    Livewire::test(Transfer::class, ['options' => $options])
        ->assertSee('No items selected')->call('choose', 0)->assertDontSeeHtml('data-slot="empty"')
        ->set('query', 'alp')->assertSee('Alpha')->assertSee('No matching results')->assertDontSee('All items selected')
        ->set('query', '')->call('moveAll', true)->assertSee('All items selected')->assertSee('Beta')
        ->set('query', 'missing')->assertSee('All items selected')->assertSee('No matching results')
        ->set('query', '')->call('moveAll', false)->assertSee('No items selected')->assertSee('Alpha');
});
