# 数据表格重设计规格

评审稿。页面持有 Livewire 状态并负责查询和业务操作。数据表格提供可组合的 Blade 子组件，承担表格交互与渲染。普通列表与树状表格共用同一个调用入口。

## 页面与组件的职责

| 页面 | 组件 |
| --- | --- |
| 持有搜索、筛选、排序、分页、每页条数和选择状态 | 提供表头排序菜单、选择框、分页按钮、每页条数选择和对应状态反馈 |
| 根据当前状态查询数据、限制查询范围、定义默认排序 | 渲染页面传入的记录，读取 paginator 的页码 |
| 声明列、筛选控件、业务操作、详情内容和汇总口径 | 渲染默认单元格、表头、空态、行详情结构、树状层级和汇总单元格 |
| 发起并授权编辑、导出等业务操作 | 把原生 slot 放入对应位置，保留页面的 Livewire action 归属 |

表格状态使用页面现有的 `WithDataTable` 作为复用入口。组件库提供通用状态变更和查询辅助方法，页面按需在自己的查询里调用。Blade 负责初始和更新后的 HTML，Livewire 更新页面状态。Alpine 负责次要筛选折叠、树节点和行详情的展开以及必要的焦点、滚动和定位交互。

## 组件组成

| 标签 | 接口和行为 |
| --- | --- |
| `data-table` | 共用容器。页面直接摆放各子组件。`selectable` 显式开启多选。 |
| `data-table.toolbar` | `primary` 和 `secondary` 两个原生 slot。`collapsible` 开启次要筛选折叠，次要筛选初始隐藏。`clear-filters` 开启清除入口，`clear-action` 指向页面的清除方法。 |
| `data-table.view-options` | 从 `:columns` 取得列配置。在自己的列表里调整普通列的显隐与顺序，再联动表格。列布局只保留在当前页面，刷新后恢复默认。 |
| `data-table.table` | 接收 `:rows` 和 `:columns`，生成表头、表体和默认空态。`sticky-header` 固定表头。列配置的 `fixed` 指定 `start` 或 `end`，固定列锁定在对应侧。`actions` 开启操作列，`actions-title` 可选。`details` 启用行详情结构。`tree` 开启树状模式，`children-field` 默认 `children`。 |
| `data-table.row` | 接收 `:entry`，依据列定义生成普通单元格。以列键命名的 slot 覆盖对应单元格。`actions` 和 `detail` 分别承载该行业务操作和详情。多选开启时由组件生成选择框。 |
| `data-table.summary-row` | 放在表格主体的 `summary` slot 中，接收按列键组织的 `:values`，按当前可见列及顺序对齐。页面计算值。 |
| `data-table.pagination` | 接收页面的 `:paginator`，提供分页控件。页名取自该分页结果。 |
| `data-table.page-size` | 接收页面允许的 `:options`，绑定页面的每页条数状态。使用 `WithDataTable` 时，页面通过 `perPageOptions()` 声明一次选项，显示和服务端校验使用同一份列表。 |
| `data-table.total` | 接收 `:total`，显示页面给出的总数。 |
| `data-table.selection-toolbar` | 启用多选且已有选择时固定在视口底部。多个工具栏依次向上堆叠。内置选择计数和清除选择，业务按钮由默认 slot 提供。 |

列定义以稳定的列键为索引，包含标题 `title`、可选的 `sortable` 和 `fixed`。默认单元格读取该列键对应的字段。`actions` 和 `detail` 保留给结构插槽。固定列、选择列、详情入口和操作列由表格结构定位。列设置保留至少一个可见数据列。空态、详情和汇总对齐均由表格计算。

## 状态规则

- **排序**：每个可排序表头用一个按钮显示列标题与当前方向，点击后弹出「默认」「升序」「降序」。同时只启用一列，切换排序列时上一列回到默认。选「默认」恢复页面查询定义的顺序。
- **筛选**：分类是页面的一种筛选。次要筛选折叠只改变显示，已生效条件继续生效。清除筛选由页面清空搜索词、主要筛选、次要筛选和分类，并清空选择、回到第一页，保留排序与列布局。
- **分页**：搜索、筛选、排序和每页条数变化时重置对应的分页器。分页、排序和每页条数变化保留选择，搜索或筛选变化默认清空选择。
- **多选**：选择状态保存行 ID。全选和取消全选作用于当前页可见且可选的行，保留其他页的选择。树表父子行独立选择，折叠保留已选子行。详情行和汇总行属于展示结构。
- **详情**：页面提供某行的 `detail` slot 时，该行可展开。组件渲染详情行并自动跨越可见列。树节点展开入口在树列中，本行详情使用独立入口。需要额外读取数据时，由页面发起。
- **汇总**：页面按业务口径计算并按列键传值。调整列显示与顺序只改变汇总单元格的呈现。

## 普通分页表调用

示例以现有 User 模型为例。`editUser` 和 `exportSelected` 代表业务页面的现有方法，页面负责校验、授权和执行。组件接口以下列调用为评审目标。

```php
use App\Models\User;
use Esoboy\Ui\Livewire\WithDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    public const COLUMNS = [
        'name' => ['title' => '姓名', 'sortable' => true, 'fixed' => 'start'],
        'email' => ['title' => '邮箱', 'sortable' => true],
        'role' => ['title' => '角色'],
        'status' => ['title' => '验证状态'],
    ];

    public string $role = '';

    public bool $onlyVerified = false;

    protected function dataTablePageName(): string
    {
        return 'users-page';
    }

    public function perPageOptions(): array
    {
        return [10, 25, 50];
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->when($this->search !== '', function (Builder $query): void {
                $pattern = "%{$this->search}%";

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $pattern)
                    ->orWhereLike('email', $pattern));
            })
            ->when(
                in_array($this->role, ['admin', 'editor', 'member'], true),
                fn (Builder $query) => $query->where('role', $this->role),
            )
            ->when(
                $this->onlyVerified,
                fn (Builder $query) => $query->whereNotNull('email_verified_at'),
            );

        $sortable = array_keys(array_filter(
            self::COLUMNS,
            fn (array $column) => $column['sortable'] ?? false,
        ));

        return $this->sortQuery($query, $sortable)
            ->orderBy('id')
            ->paginate($this->perPage, pageName: $this->dataTablePageName());
    }

    public function updatedRole(): void
    {
        $this->resetUserResults();
    }

    public function updatedOnlyVerified(): void
    {
        $this->resetUserResults();
    }

    public function clearUserFilters(): void
    {
        $this->search = '';
        $this->role = '';
        $this->onlyVerified = false;
        $this->resetUserResults();
    }

    private function resetUserResults(): void
    {
        $this->selected = [];
        $this->resetPage($this->dataTablePageName());
    }
};
```

```blade
@php($users = $this->users)
@php($verifiedCount = $users->getCollection()->whereNotNull('email_verified_at')->count())

<x-ui::data-table selectable>
    <x-ui::data-table.toolbar collapsible clear-filters clear-action="clearUserFilters">
        <x-slot:primary>
            <x-ui::input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="搜索姓名或邮箱"
                aria-label="搜索姓名或邮箱"
            />
            <select wire:model.live="role" aria-label="角色">
                <option value="">全部角色</option>
                <option value="admin">管理员</option>
                <option value="editor">编辑</option>
                <option value="member">成员</option>
            </select>
        </x-slot:primary>

        <x-slot:secondary>
            <label>
                <x-ui::checkbox wire:model.live="onlyVerified" />
                仅显示已验证用户
            </label>
        </x-slot:secondary>
    </x-ui::data-table.toolbar>

    <x-ui::data-table.view-options :columns="$this::COLUMNS" />

    <x-ui::data-table.table
        :rows="$users"
        :columns="$this::COLUMNS"
        sticky-header
        actions
        actions-title="操作"
        details
    >
        @foreach ($component->entries() as $entry)
            @php($user = $entry->row)

            <x-ui::data-table.row :entry="$entry">
                <x-slot:status>
                    <x-ui::badge>{{ $user->email_verified_at ? '已验证' : '待验证' }}</x-ui::badge>
                </x-slot:status>

                <x-slot:actions>
                    <x-ui::button wire:click="editUser({{ $user->id }})">编辑</x-ui::button>
                </x-slot:actions>

                <x-slot:detail>
                    <p>注册时间 {{ $user->created_at?->format('Y-m-d H:i') }}</p>
                </x-slot:detail>
            </x-ui::data-table.row>
        @endforeach

        <x-slot:summary>
            <x-ui::data-table.summary-row
                :values="['name' => '本页合计', 'status' => $verifiedCount.' 人已验证']"
            />
        </x-slot:summary>
    </x-ui::data-table.table>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui::data-table.total :total="$users->total()" />
        <x-ui::data-table.page-size :options="$this->perPageOptions()" />
        <x-ui::data-table.pagination :paginator="$users" />
    </div>

    <x-ui::data-table.selection-toolbar>
        <x-ui::button wire:click="exportSelected">导出所选</x-ui::button>
    </x-ui::data-table.selection-toolbar>
</x-ui::data-table>
```

## 树状表格调用

`$teams` 是页面准备的原始嵌套数据。每条记录有稳定的 `id`，`items` 是其子记录集合。树表的列配置和汇总值沿用相同结构。

```blade
<x-ui::data-table selectable>
    <x-ui::data-table.table
        :rows="$teams"
        :columns="$columns"
        tree
        children-field="items"
        actions
        actions-title="操作"
    >
        @foreach ($component->entries() as $entry)
            <x-ui::data-table.row :entry="$entry">
                <x-slot:actions>
                    <x-ui::button wire:click="editTeam({{ $entry->row['id'] }})">
                        编辑
                    </x-ui::button>
                </x-slot:actions>
            </x-ui::data-table.row>
        @endforeach

        <x-slot:summary>
            <x-ui::data-table.summary-row :values="$summary" />
        </x-slot:summary>
    </x-ui::data-table.table>
</x-ui::data-table>
```

`entries()` 由类式 Blade 表格组件提供，普通模式按传入顺序遍历，树状模式根据配置的字段展开嵌套记录并携带层级信息。页面只写一层循环，插槽直接使用当前行变量。叶子节点由组件根据已提供的子数据判断。

## 实施与验收

1. **组件骨架**。在 `package/src/UiServiceProvider.php` 注册类式表格主体，复用现有 Blade 表格部件。迁移容器、表头、行、固定列、默认空态和汇总行。验收不同列顺序、隐藏列、缺少操作列、自定义单元格和行操作的渲染。
2. **状态和独立子组件**。收敛 `package/src/Livewire/WithDataTable.php` 的表格交互方法，复用现有 `view-options` 和分页部件，拆出每页条数和总数。迁移工具栏的两个 slot 和多选工具栏。验收 Select 的单列排序与恢复默认、命名分页、刷新恢复列布局、固定列锁定、清除筛选、跨页选择。
3. **树与详情**。复用行展开部件，将树节点展开和详情展开拆成独立交互。验收自定义 `children-field`、空子节点、折叠后可见行全选、父子独立选择、已选子行保留、汇总跟随列顺序。
4. **迁移调用与文档**。更新 `application/resources/views/pages/⚡data.blade.php`、包的使用说明和 `UPGRADING.md`，直接迁移受影响的调用。更新 `package/tests/Feature/DataTableTest.php` 与 `application/tests/Browser/DataTableTest.php`。浏览器测试覆盖页面更新后 slot 内容同步，行操作、详情和批量工具栏按钮由页面 Livewire 执行。

相关决策见 [查询、状态和业务操作归页面](../../docs/adr/0001-data-table-page-ownership.md) 与 [页面组合数据表格的子组件](../../docs/adr/0002-data-table-composition.md)。
