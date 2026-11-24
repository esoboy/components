<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Support\DataTableColumns;
use Illuminate\Database\Eloquent\Collection as Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\WithPagination;

class EmbeddedPaginationExample extends Component
{
    public function render(): string
    {
        return <<<'BLADE'
            <div>
                @php($pager = (new \Illuminate\Pagination\LengthAwarePaginator([['id' => 3]], 5, 1, 3, ['path' => '/orders', 'pageName' => 'ordersPage']))->appends(['status' => 'open'])->fragment('list'))
                <x-ui::pagination :paginator="$pager" jumpable />
                <x-ui::data-table :rows="$pager" :columns="['id' => ['label' => 'Order']]" />
            </div>
        BLADE;
    }
}

class LivePaginationExample extends EmbeddedPaginationExample
{
    use WithPagination;
}

it('uses paginator URLs inside a host without pagination actions and the named page on a paginating host', function () {
    $ordinary = HTMLDocument::createFromString(Livewire::test(EmbeddedPaginationExample::class)->html(), LIBXML_NOERROR);
    expect($ordinary->querySelector('.ui-pagination-next')->getAttribute('href'))->toBe('/orders?status=open&ordersPage=4#list')
        ->and($ordinary->querySelector('.ui-data-table .ui-pagination-next')->getAttribute('href'))->toBe('/orders?status=open&ordersPage=4#list')
        ->and($ordinary->querySelector('[data-page-name]')->getAttribute('x-data'))->toBe('uiPagination(false)');

    $live = Livewire::test(LivePaginationExample::class);
    $document = HTMLDocument::createFromString($live->html(), LIBXML_NOERROR);
    expect($document->querySelector('.ui-pagination-next')->getAttribute('wire:click'))->toBe("nextPage('ordersPage')")
        ->and($document->querySelector('.ui-data-table .ui-pagination-next')->getAttribute('wire:click'))->toBe("nextPage('ordersPage')")
        ->and($document->querySelector('[data-page-name]')->getAttribute('x-data'))->toBe('uiPagination(true)');
    $live->call('gotoPage', 4, 'ordersPage')->assertSet('paginators.ordersPage', 4);
});

class DataTableHostExample extends Component
{
    public array $selection = [];

    public array $layout = [];

    public bool $allowed = true;

    public bool $empty = false;

    public string $status = 'Ready';

    public static array $rendered = [];

    public static int $bulkRenders = 0;

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                @php
                    $columns = ['number' => ['label' => 'Order'], 'status' => ['label' => 'Status'], 'cost' => ['label' => 'Cost']];
                    if (! $allowed) unset($columns['cost']);
                    $rows = $empty ? [] : [['id' => 0, 'number' => '<A>', 'status' => 'raw', 'cost' => 5], ['id' => 'b&c', 'number' => '2', 'status' => 'raw', 'cost' => 9]];
                @endphp
                <x-ui::data-table :rows="$rows" :columns="$columns" layout-model="layout" wire:model.live="selection">
                    <x-slot:toolbar><x-ui::data-table.column-settings /></x-slot:toolbar>
                    @foreach ($rows as $row)
                        <x-ui::data-table.cell column="status" :row="$row">
                            @php(\DataTableHostExample::$rendered[] = $row['id'])
                            <x-ui::badge>{{ $status }}</x-ui::badge>
                        </x-ui::data-table.cell>
                        <x-ui::data-table.cell column="cost" :row="$row"></x-ui::data-table.cell>
                    @endforeach
                    <x-slot:selection>
                        <x-ui::data-table.selection-toolbar>
                            @php(\DataTableHostExample::$bulkRenders++)
                            <x-ui::select id="bulk-priority" :options="[['value' => 'urgent', 'label' => 'Urgent']]" />
                        </x-ui::data-table.selection-toolbar>
                    </x-slot:selection>
                </x-ui::data-table>
            </div>
        BLADE;
    }
}

it('renders a hand-authored table with native sections, spans and row attributes in plain Blade', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui::table aria-label="Invoices">
            <x-ui::table.caption>Payments</x-ui::table.caption>
            <x-ui::table.header><x-ui::table.row><x-ui::table.head colspan="2">Invoice</x-ui::table.head></x-ui::table.row></x-ui::table.header>
            <x-ui::table.body>
                <x-ui::table.row data-state="selected"><x-ui::table.cell rowspan="2">A</x-ui::table.cell><x-ui::table.cell>1</x-ui::table.cell></x-ui::table.row>
                <x-ui::table.row><x-ui::table.cell>2</x-ui::table.cell></x-ui::table.row>
                <x-ui::table.empty colspan="2">End</x-ui::table.empty>
            </x-ui::table.body>
            <x-ui::table.footer><x-ui::table.row><x-ui::table.cell colspan="2">Total</x-ui::table.cell></x-ui::table.row></x-ui::table.footer>
        </x-ui::table>
        BLADE);
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    expect($xpath->evaluate('string(//table[@aria-label="Invoices"]/caption)'))->toBe('Payments')
        ->and($xpath->evaluate('string(//thead/tr/th[@colspan=2])'))->toBe('Invoice')
        ->and($xpath->evaluate('string(//tbody/tr[@data-state="selected"]/td[@rowspan=2])'))->toBe('A')
        ->and(trim($xpath->evaluate('string(//tbody/tr[last()]/td[@colspan=2])')))->toBe('End')
        ->and($xpath->evaluate('string(//tfoot/tr/td[@colspan=2])'))->toBe('Total')
        ->and($xpath->evaluate('count(//input | //button | //*[contains(concat(" ", normalize-space(@class), " "), " ui-data-table ")])'))->toBe(0.0);
});

it('renders cells only for real rows and authorized visible columns in the caller', function () {
    DataTableHostExample::$rendered = [];
    $host = Livewire::test(DataTableHostExample::class)
        ->assertSee('&lt;A&gt;', false)->assertSee('Ready')->assertDontSee('raw');
    expect(DataTableHostExample::$rendered)->toBe([0, 'b&c']);
    $dom = new DOMDocument;
    @$dom->loadHTML($host->html());
    $xpath = new DOMXPath($dom);
    expect($xpath->evaluate('count(//*[contains(concat(" ", normalize-space(@class), " "), " ui-table-container ")]/table[contains(concat(" ", normalize-space(@class), " "), " ui-table-element ")])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//td[@data-column="cost"])'))->toBe(2.0)
        ->and(trim($xpath->evaluate('string(//td[@data-column="cost"])')))->toBe('');
    $host->set('status', 'Updated')->assertSee('Updated');
    DataTableHostExample::$rendered = [];
    $host->set('layout', ['order' => ['cost', 'status', 'number'], 'hidden' => ['status']])
        ->set('allowed', false)->assertDontSee('Updated')->assertDontSee('Cost');
    expect(DataTableHostExample::$rendered)->toBe([]);
    $host->set('empty', true)->set('layout', [])->assertSee('Order')->assertSee('Status')
        ->assertSee('No records')->assertSeeHtml('colspan="3"');
    expect(DataTableHostExample::$rendered)->toBe([]);
});

it('renders default and custom cells from the same single-pass row source', function () {
    $rows = (function () {
        yield ['id' => 5, 'number' => 'Order 5', 'status' => 'ready'];
        yield ['id' => 2, 'number' => 'Order 2', 'status' => 'sent'];
    })();
    $columns = ['number' => ['label' => 'Number'], 'status' => ['label' => 'Status']];
    $html = Blade::render(<<<'BLADE'
        <x-ui::data-table :rows="$rows" :columns="$columns">
            <x-slot:toolbar><x-ui::badge>Current orders</x-ui::badge></x-slot:toolbar>
            @foreach ($component->records as $order)
                <x-ui::data-table.cell column="status" :row="$order">
                    <x-ui::badge>Custom {{ $order['status'] }}</x-ui::badge>
                </x-ui::data-table.cell>
            @endforeach
        </x-ui::data-table>
        BLADE, compact('rows', 'columns'));
    $cells = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('tbody td');
    expect(array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($cells)))
        ->toBe(['Order 5', 'Custom ready', 'Order 2', 'Custom sent']);
});

it('mounts nested bulk tools only while selected records exist', function () {
    DataTableHostExample::$bulkRenders = 0;
    $host = Livewire::test(DataTableHostExample::class)->assertDontSee('bulk-priority');
    expect(DataTableHostExample::$bulkRenders)->toBe(0);
    $host->set('selection', ['0'])->assertSeeHtml('id="bulk-priority"');
    $host->set('selection', [])->assertDontSee('bulk-priority');
    expect(DataTableHostExample::$bulkRenders)->toBe(1);
    $host->set('selection', ['b&c'])->assertSeeHtml('id="bulk-priority"');
});

it('retains static headers on the first empty response and supports actual model keys and safe field values', function () {
    DataTableHostExample::$rendered = [];
    Livewire::test(DataTableHostExample::class, ['empty' => true])->assertSeeHtml('colspan="4"')->assertSee('Order')->assertSee('Columns');
    expect(DataTableHostExample::$rendered)->toBe([]);
    $model = new class extends Model {};
    $model->setKeyName('uuid')->forceFill(['uuid' => 'row-0', 'price' => 0, 'title' => new HtmlString('<strong>unsafe</strong>')]);
    $rows = new Models([$model]);
    $columns = ['total' => ['label' => 'Total', 'field' => 'price', 'align' => 'end'], 'title' => ['label' => 'Title']];
    $html = Blade::render('<x-ui::data-table :rows="$rows" :columns="$columns" />', compact('rows', 'columns'));
    expect(substr_count($html, 'data-column="total" data-align="end"'))->toBe(2);
    expect($html)->toContain('&lt;strong&gt;unsafe&lt;/strong&gt;')->toMatch('/data-column="total"[^>]*>\s*0\s*<\/td>/')
        ->not->toContain('type="checkbox"');
    expect(Blade::render('<x-ui::data-table.total :value="$rows" /><x-ui::data-table.total :value="0" />', compact('rows')))
        ->toContain('1 record', '0 records');
    $rows = [['uuid' => 'key-1', 'price' => 1]];
    expect(Blade::render('<x-ui::data-table row-key="uuid" :rows="$rows" :columns="$columns" />', compact('rows', 'columns')))
        ->toContain('table-row-key-1');
});

it('uses one current-page window and never mistakes a cursor or simple page slice for the full total', function () {
    $models = [['id' => 1], ['id' => 2]];
    $columns = ['id' => ['label' => 'ID']];
    $rows = new LengthAwarePaginator($models, 31, 2, 5, ['path' => '/orders', 'pageName' => 'ordersPage']);
    $markup = '<x-ui::data-table :rows="$rows" :columns="$columns"><x-slot:footer><x-ui::data-table.total /><x-ui::pagination :paginator="$rows" :max-pages="3" jumpable /></x-slot:footer></x-ui::data-table>';
    $html = Blade::render($markup, compact('rows', 'columns'));
    expect($html)->toContain('31 records', 'href="/orders?ordersPage=4"', 'href="/orders?ordersPage=6"', 'name="pageNumber"')
        ->not->toContain('href="/orders?ordersPage=7"');
    $form = Blade::render('<form>'.$markup.'</form>', compact('rows', 'columns'));
    expect(substr_count($form, '<form'))->toBe(1)->and($form)->toContain('form=""');
    foreach ([new Paginator($models, 1), new CursorPaginator($models, 1, options: ['parameters' => ['id']])] as $rows) {
        $html = Blade::render($markup, compact('rows', 'columns'));
        expect($html)->toContain('Next')->not->toContain('class="ui-data-table-total"', 'name="pageNumber"');
    }
});

it('renders optional jump controls in a standalone Pagination and preserves its named URL', function () {
    $pager = (new LengthAwarePaginator([], 90, 10, 3, ['path' => '/orders', 'pageName' => 'invoices']))
        ->appends(['status' => 'open'])->fragment('list');
    $without = Blade::render('<x-ui::pagination :paginator="$pager" />', compact('pager'));
    expect($without)->toContain('aria-current="page"')->not->toContain('class="ui-pagination-jump"');

    $with = Blade::render('<x-ui::pagination :paginator="$pager" :max-pages="3" jumpable />', compact('pager'));
    expect($with)->toContain('data-page-name="invoices"', 'status=open&amp;invoices=1#list', 'max="9"', 'class="ui-pagination-jump"')
        ->not->toContain('class="ui-data-table"');

    $custom = Blade::render('<x-ui::pagination :paginator="$pager" jumpable><x-ui::pagination.content><x-ui::pagination.item>Custom links</x-ui::pagination.item></x-ui::pagination.content></x-ui::pagination>', compact('pager'));
    expect($custom)->toContain('Custom links', 'class="ui-pagination-jump"')->not->toContain('class="ui-pagination-button"');
});

it('resolves stale layouts, preserves locked positions, and keeps a business column visible', function () {
    $columns = ['a' => ['label' => 'A'], 'end' => ['label' => 'Edit', 'field' => null, 'fixed' => 'end'],
        'lock' => ['label' => 'L', 'reorderable' => false], 'b' => ['label' => 'B']];
    $layout = new DataTableColumns($columns, ['order' => ['b', 'removed', 'a'], 'hidden' => ['end', 'lock', 'removed']]);
    expect(array_keys($layout->visible))->toBe(['b', 'a', 'end'])
        ->and($layout->isCustomized)->toBeTrue()
        ->and(array_keys($layout->ordered))->toBe(['b', 'lock', 'a', 'end'])
        ->and($layout->canToggle('end'))->toBeFalse();
    $layout = new DataTableColumns($columns, ['hidden' => ['a', 'lock', 'b']]);
    expect(array_keys($layout->visible))->toBe(['a', 'end'])->and($layout->canToggle('a'))->toBeFalse();
    $layout = new DataTableColumns($columns, ['order' => ['removed', 'a', 'b'], 'hidden' => ['removed', 'end']]);
    expect($layout->isCustomized)->toBeFalse();
});

it('rejects ambiguous row identities before rendering custom cells', function (array $rows) {
    $columns = ['name' => ['label' => 'Name']];
    Blade::render('<x-ui::data-table :rows="$rows" :columns="$columns" />', compact('rows', 'columns'));
})->with([[[['id' => 1], ['id' => '1']]], [[['name' => 'missing']]]])->throws(ViewException::class);
