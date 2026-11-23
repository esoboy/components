# 状态与组合

所有公开组件都使用 `<x-ui::…>`。内部 `ui::` Livewire 类是实现细节，项目不直接调用。组件接受结构化 props 与 Blade slot，内容由 Blade 渲染；Editor 编辑区和 Signature 的 canvas 绘制是明确的浏览器渲染例外，见 [内容契约](content.md)。

## 唯一值来源

Select、Combobox、IconSelect、Command、Questionnaire、TagsInput、Calendar、DatePicker、DatetimePicker、TimePicker、Tree、TreeSelect、Cascader、Transfer、Mentions 等独立状态控件使用以下三种值来源。

| 调用 | 权威来源 | 交互后的行为 |
| --- | --- | --- |
| `wire:model="role"` | 调用方 Livewire 属性 | 原生 `#[Modelable]` 双向绑定；`.live` 决定是否立即同步父页面 |
| `:value="$role"` | 外部 prop | 发出 `value-changed`，组件不自行修改该值；父页面接受后重新传入 |
| `:initial-value="'reader'"` | 内部 Livewire value | 首次挂载初始化一次；后续父级刷新保留内部状态 |
| 不传值 | 内部 Livewire value | 初值为 null，各组件按自己的值契约解释空值 |

`wire:model`、`value`、`initial-value` 混用会抛出异常。不要在同一实例生命周期内切换模式；确有需要时用新的 `wire:key` 创建新实例。null、0、空字符串不会通过真假判断被替换为默认值；某一组件是否接受它由具体类型契约决定。禁用或只读状态在服务端变更入口也受约束，不能仅靠前端样式阻止修改。

```blade
<x-ui::select id="approval-role" :value="$approvedRole" :options="$roles"
    @value-changed="requestRole($event.detail.value)" />

<x-ui::select id="local-role" :initial-value="'reader'" :options="$roles" />
```

`requestRole(string $value)` 是父组件方法；由父级决定接受、拒绝或延迟更新 `$approvedRole`。库内仅做结构、范围与禁用项验证，业务授权和验证仍需项目负责。

普通 Input、Textarea、Checkbox、Radio、Switch、NativeSelect 等直接输出原生表单元素，使用原生 `value` / `checked` 与 Livewire `wire:model`。它们没有独立服务端实例，也不套用 `initial-value` 或 `value-changed` 协议。Tabs、Dialog 等只维护浏览器显示状态，通过 `x-modelable` 与 `wire:model` 接入父级时仍使用 Livewire 的同步机制；显示切换不会在浏览器生成内容。

### NumberInput、Slider、InputOtp、Editor、Cropper、Signature

这六个组件没有内部 Livewire 实例或 snapshot，直接连接所属 Livewire。Blade 输出控件、滑块/OTP 格子数量、提示与 slot；Alpine 只保存原生输入、拖动、签名笔画或编辑器所需的即时缓冲，通过公开输入事件交给 `wire:model` 处理，不复制业务状态或执行保存。

| 调用 | 权威来源 | 生命周期与提交 |
| --- | --- | --- |
| `wire:model="fields.quantity"` | 调用方 Livewire 属性 | 普通绑定暂存于调用方客户端状态，下一次请求提交；父级回填同步到控件 |
| `:value="$quantity"` | 父级 prop | `value-changed` 的 `detail.value` 请求变更；未接受时保持外部值 |
| `:initial-value="2"` | 本实例的原生输入/编辑器缓冲 | 仅挂载时初始化；父级重绘不会重新应用初值；通过 `name` 参加原生表单提交 |
| 不传值 | 同上 | 初值为 null，按控件的空值语义展示 |

三种来源仍互斥。模式、绑定路径以及 Editor 输入/输出格式变更时使用新的 `wire:key`。多个实例绑定同一属性时提供不同 id，它们共享父级权威值；不同属性及非受控实例相互独立。没有父 Livewire 的普通 Blade 页面也支持非受控表单，仍需按安装文档初始化库的 Alpine 行为。

```blade
<x-ui::number-input id="quantity" wire:model="fields.quantity" :min="0" :max="100" />
<x-ui::slider id="range" wire:model.live.debounce.200ms="range" />
<x-ui::input-otp id="approval-code" :value="$approvedCode"
    x-on:value-changed="$wire.requestCode($event.detail.value)" />
<x-ui::editor id="draft" name="draft" initial-value="**初始内容**" />
```

受控事件使用 Alpine 的 `x-on:value-changed`，调用父方法时显式写 `$wire`。父级可接受、拒绝或延迟接受；受控 Slider / Cropper 在指针拖动结束后请求更新；Signature 在每笔结束或清空时请求更新。非受控值不会作为隐藏的服务端属性自动写入父组件；需要 Livewire 保存时使用 `wire:model`。

修饰符由 Livewire 4 处理，位置有含义：`wire:model.blur` 在离开整个控件时才更新客户端属性，但不自动请求；`wire:model.live.blur` 立即更新客户端属性、失焦时请求；`wire:model.change.live` 在变更提交时更新并请求。NumberInput 原生 change/步进、Slider 键盘/拖动结束、OTP 输入、Editor 编辑后离开整个控件、Cropper 拖动结束/键盘微调、Signature 完成笔画/清空构成各自的 change。进入组件内其他格子或工具栏不算离开。Editor 连续编辑建议使用 `wire:model.live.debounce.300ms`。

`<button type="reset">` 恢复本次挂载的初值；受控模式只请求父级恢复初值。新 key 或销毁后重新挂载才使用最新 initial-value。禁用字段不参加 FormData；readonly 仍参加。渲染时校验配置与传入值，浏览器限制输入；项目必须在所属 Livewire 保存动作中验证业务数据与权限，客户端 readonly/min/max 不替代服务端验证。

控件可以放在表单外。给控件传入 `form="profile"` 和 `name`，浏览器会将字段提交给 `<form id="profile">`。这同样适用于 Editor、Cropper、Signature、Rating 和选择控件。

NumberInput 允许暂时超出范围的输入。例如，最小值为 10 时可以先键入 `2`，再键入 `5`。失焦或按 Enter 时，仍超出范围的数值会调整到最近的边界。Editor 重置时按 `output-type` 发送挂载时的正文，受控模式下表单继续提交父级接受的值。

## 稳定身份与属性

独立状态控件和上述六个直接绑定控件在父 Livewire 中必须有稳定身份，优先级为 `wire:key` → `id` → model 属性名 → `name`。没有绑定也没有身份时直接报错。列表内推荐显式业务 key；同一 model 的多个同类控件须提供不同 id。不要用随机数、数组位置或每次渲染变化的值当 key。

```blade
@foreach ($rows as $row)
    <x-ui::select :wire:key="'row-role-'.$row->id" :id="'row-role-'.$row->id"
        wire:model="roles.{{ $row->id }}" :options="$roles" />
@endforeach
```

`id` 与 ARIA 描述传到输入或触发控件，样式等容器属性保留在状态控件容器。`name` 在选择类组件中输出隐藏 input；多值使用 `name[]`，无选择不输出该字段。单独依赖原生表单提交时，项目应明确空值语义。绑定的普通输入若省略 id，会根据 model 属性生成 id；重复呈现同一 model 时仍须手动指定唯一 id。

NumberInput 的 `required`、`placeholder`、`form` 和 ARIA 属性传到原生数字输入框。OTP 和 Slider 有多个输入时，首项沿用显式或由 model 生成的 id，后续项依次加上 `-1`、`-2`。只提供 `name` 时省略输入的 id，仍可提交表单。需要用外部 `<label for="…">` 关联控件时，请显式传入 `id`。

## 选项与树

```php
$options = [
    ['value' => 0, 'label' => '未分组'],
    ['value' => 'team', 'label' => '团队', 'disabled' => true],
];
$nodes = [
    ['value' => 'framework', 'label' => '框架', 'children' => [
        ['value' => 'blade', 'label' => 'Blade'],
    ]],
];
```

选项可额外提供 group（连续同组的标题）和 shortcut（展示用快捷键文字）。快捷键的业务动作由页面绑定。标识为 string 或 int，单选值为标识或 null，多选值为标识列表。禁止重复标识，包括会形成相同 HTML key 的 `1` 与 `'1'`。树最多 64 层。

Cascader、Tree 和 TreeSelect 默认只选叶节点。传入 `select-parents` 后，父级名称前显示独立的选择框，单选为圆框，多选为方框。点击父级名称或箭头可浏览下级，点击叶节点名称直接选择。多选时父级和子级可分别勾选和取消，例如 `[0, 'hz']`。省略属性或传入 `:select-parents="false"` 即可关闭父级选择。

Cascader 勾选父级时保留正在浏览的列。首次挂载和外部回填时定位到选中节点，多选定位最后一个值。普通父级刷新保留浏览路径，节点替换后重新核对该路径。

Combobox 在输入框内搜索，多选值显示在同一字段的 chips 中，clearable 可显示清除按钮。Combobox / Command 在服务端过滤传入选项，输入防抖 200 ms；`query-changed` 提供 query，父页面可据此查询并更新 `options`。组件不接收 URL、Eloquent 模型或数据库查询构造器。不以 Alpine 筛选、渲染或虚拟化列表；大数据集由父级搜索和分页后传入，避免巨大 snapshot 与 HTML。

Combobox 的 `auto-highlight` 在服务端返回搜索结果后高亮首个可用选项。`search-in-popup` 将搜索框放入弹层，`search-placeholder` 设置该搜索框提示。选中后恢复选项标签，关闭搜索时保留已选值。多选标签保持值列表的顺序。方向键在标签间移动，Backspace / Delete 删除标签，Enter 选择后收起列表，列表关闭时 Escape 清空值。禁用与只读限制同样在服务端值变更入口执行。

父级按搜索词替换 `options` 时，Combobox 保留正在输入的搜索词，以及已经取得的已选项标签。首次回填已有值时，将对应选项一并传入即可立即显示标签。多选项移出当前结果页后，用户仍可通过标签移除它。

IconSelect 复用 Combobox 的搜索与单选交互。展开后每页显示最多 32 个图标及其正式名称，可用翻页按钮或 `PageUp`、`PageDown` 浏览整个 Lucide 图标库。输入部分名称会搜索全部图标，并从第一页显示匹配项。再次展开时会定位到已选图标所在页。选择后，`wire:model` 取得名称字符串，例如 `circle-check`。设置 `name` 后，普通表单也会提交该名称。清除选择后，绑定值为 `null`。

```blade
<x-ui::icon-select wire:model="icon" name="icon" clearable aria-label="选择图标" />
```

在双向绑定和内部值模式下，选中后即可在输入框和原生表单中读取名称。`wire:model.live` 将父级绑定和本次选择一起提交，父组件在更新钩子中校正的值会回填到控件。使用 `:value` 时，控件在父级接受变更后显示新值。

选项的 `description` 接受字符串或字符串片段列表，适用于选项内的说明行；片段仍逐段由 Blade 转义。菜单导航使用 `dropdown-menu.link`、`context-menu.link` 或 `menubar.link`，它们输出语义化链接，并复用菜单项的样式、键盘导航和关闭行为。

Calendar / DatePicker 接受 `Y-m-d`，`multiple` 为日期列表，`range` 为有序的 [开始日期, 结束日期]（未完成时为单元素列表），与 multiple 互斥；允许同一天起止。`min` / `max` 为可选 ISO 边界。支持 number-of-months=1–12、caption-layout=label/dropdown、week-starts-on=0–6、show-week-number、disabled-dates；区间不能穿过禁用日期。显示月份独立保留，父级修改选中日期不会强行重置用户正在浏览的月份。NumberInput 接受数值或 null，支持 min / max / step。TagsInput 接受去重字符串列表，支持 max / max-length。Transfer 固定接受多值列表，全选只移动未禁用的项目，不提供 multiple / placeholder。Tree、TreeSelect、Cascader 只接收 nodes，不接收 options；Tree 没有 placeholder，TreeSelect / Cascader 仍支持。

原生表单中，Calendar 和 DatePicker 的单日使用 `name`，多选和区间使用 `name[]`。区间只选起点时提交一项，选完后按起止顺序提交两项。仅指定未来的 `min` 或过去的 `max` 时，日历会打开到可浏览范围内，选中值保持原样。

区间还只有起点时，如果父级收紧边界或禁用该日，下次点击的可选日期成为新起点。再次选择终点后提交完整区间。

DatePicker 和 DatetimePicker 的标题默认使用 Calendar 的月份、年份下拉框。切换年月只更新浏览月份，弹层保持打开。Calendar 默认使用 `caption-layout="label"`，显式设置 `dropdown` 可启用相同标题。Select 和 NativeSelect 均支持 `size="default"`（32px）及 `size="sm"`（28px）。

| 入口 | 选择值 | 启用 `clearable` 后清空为 |
| --- | --- | --- |
| `date-picker` | 单个 `2026-11-18`，或多选、范围的日期列表 | 单选 `null`，多选和范围 `[]` |
| `time-picker` | 24 小时制 `09:30`，启用 `seconds` 后为 `09:30:45` | `null` |
| `datetime-picker` | 单个本地日期时间 `2026-11-18T09:30`，支持 `seconds` | `null` |

TimePicker 和 DatetimePicker 复用同一个时间面板。每次打开时数字列表均收起，点击时、分或秒只展开对应的列表，再点一次可收起。选好数字后值立即生效，列表收起，焦点回到该时间段。

DatetimePicker 首次选日期时以午夜为初值，之后换日期保留时间，调整时间保留日期。数字列表使用 ScrollArea。用 Tab 聚焦时间段，Enter 或空格展开，方向键移动焦点，Home 和 End 跳到首末项。点弹层外部或按 Escape 可关闭。业务需要原生时间输入时使用 `<x-ui::input type="time" />`。

TimePicker、DatePicker 和 DatetimePicker 只在提供 `extra` 插槽时显示弹层页脚，插槽内可以使用调用方的 Livewire 操作和绑定。DatePicker 和 DatetimePicker 还支持用 `footer` 在字段下方显示辅助内容。

```blade
<x-ui::date-picker wire:model.live="deliveryDate" clearable>
    <x-slot:extra>
        <x-ui::button variant="ghost" wire:click="useToday">今天</x-ui::button>
    </x-slot:extra>
</x-ui::date-picker>
<x-ui::datetime-picker wire:model="appointment" seconds clearable />
```

InputGroup 的 input/textarea/button 分别复用 Input、Textarea、Button；addon 的 `align` 为 inline-start / inline-end / block-start / block-end。PasswordInput、NumberInput 和 Combobox 同样通过 InputGroup 组合。菜单 checkbox-item / radio-item 复用 Checkbox / Radio，指示图标由 Blade 输出。

ScrollArea 的 `orientation` 为 vertical（默认）或 horizontal，内容继续由 slot 渲染，原生 viewport 接收键盘滚动，内部 Alpine 只同步及拖动滚动条。Dialog / Sheet 的 `show-close-button` 默认为 true；Drawer 的 `show-swipe-handle` 默认为 false，需要拖动手柄时显式启用。ToggleGroup 的 `spacing` 默认 2（8px），连接按钮可显式设为 0。Toast 的内容通过 `toast.content` 包裹，里面组合标题、说明和操作。

Mentions 继续保留一个内部 Livewire 管理正文和候选筛选。普通输入使用延迟值同步；仅文本末尾存在 @query 且查询词改变时防抖 200 ms 请求候选，同一查询词不重复过滤。候选由 Blade 渲染，浏览器仅隐藏已过期列表；选择时服务端再次核对查询词。父级提交会带上最新正文，显式 wire:model.live 仍会按调用方要求同步。

## 插槽与组合边界

普通 Blade 组件的所有 slot 都在父级上下文。私有 Livewire 控件的调用方内容也保留在公开 Blade facade 的外层，不放进内部 Livewire 子组件的 DOM。这样父级刷新会更新 slot 文本，slot 内 `wire:click` 调用父级方法，slot 内嵌 Livewire 组件保留身份和状态。

Select 等状态控件的默认 slot 和 `footer` 是控件下方的辅助区域，**不在选项弹层内部**。它们的弹层仅展示结构化数据。需要任意内容的弹层时组合 Popover、Command、Button 等公开组件。

```blade
<x-ui::select id="assignee" wire:model="assignee" :options="$people">
    <x-slot:footer>
        <x-ui::button variant="ghost" wire:click="createPerson">新增人员</x-ui::button>
    </x-slot:footer>
</x-ui::select>
```

`trigger` / `close` slot 恰好一个可操作元素；行为自动附着到这个元素。不要在 slot 外手动绑定库内部事件。Accordion 使用原生 `accordion.summary`，Collapsible 使用 `collapsible.trigger`，Tabs 的语义选项使用 `tabs.tab`。

## 分页

```blade
<x-ui::pagination :paginator="$invoices" :max-pages="5" jumpable />
```

传入 Laravel 分页对象即可生成导航。对于有总页数的分页，`max-pages` 限定可见数字页码数，`jumpable` 打开手动跳页，输入有效页码后可按回车或点击跳转。使用 `WithPagination` 的 Livewire 宿主按对应页名更新。普通 Blade 页面和未提供分页动作的 Livewire 宿主沿用分页对象的 URL、查询参数和片段。DataTable 默认页脚遵循相同规则。简单分页与游标分页使用上一页和下一页。手写链接使用 `pagination.content`、`pagination.item` 和 `pagination.link` 组合。[分页调用](components.md#pagination)

## 数据表格

基础 `table` 只输出表格标签、样式和插槽，行、空态和跨列合计由页面手写。后台列表通过基础 `table` 及主题中的按钮、复选框和分页器组成。页面使用 `<x-ui::data-table :rows="$orders" :columns="$columns" />`。传入当页已经加载的记录和有序列定义。模型默认取主键，数组默认取 `id`，可用 `row-key` 指定其他字段。每条记录需要一个唯一的整数或字符串键。

```blade
@php
    $columns = [
        'number' => ['label' => '订单号', 'sortable' => true, 'fixed' => 'start'],
        'status' => ['label' => '状态'],
        'customer' => ['label' => '客户', 'field' => 'customer_name'],
    ];
@endphp
<x-ui::data-table :rows="$orders" :columns="$columns" wire:model.live="selectedOrderIds"
    sort-model="orderSort" layout-model="orderLayout">
    <x-slot:toolbar>
        <x-ui::data-table.filters collapsible :initially-collapsed="false" clear-action="clearOrderFilters">
            <x-slot:primary><x-ui::input type="search" wire:model.live.debounce.250ms="filters.search" aria-label="搜索订单" /></x-slot:primary>
            <x-slot:secondary><label><x-ui::checkbox wire:model.live="filters.unpaid" /> 仅看待付款</label></x-slot:secondary>
        </x-ui::data-table.filters>
        <x-ui::data-table.column-settings />
    </x-slot:toolbar>

    @foreach ($component->records as $order)
        <x-ui::data-table.cell column="status" :row="$order">
            <x-ui::badge>{{ $order->status_label }}</x-ui::badge>
        </x-ui::data-table.cell>
    @endforeach

    <x-slot:footer>
        <x-ui::data-table.total />
        <x-ui::data-table.page-size wire:model.live="perPage" :options="$pageSizes" />
        <x-ui::pagination :paginator="$orders" :max-pages="5" jumpable />
    </x-slot:footer>
    <x-slot:selection>
        <x-ui::data-table.selection-toolbar placement="table">
            @can('exportAny', \App\Models\Order::class)
                <x-ui::button wire:click="exportSelected">导出所选</x-ui::button>
            @endcan
        </x-ui::data-table.selection-toolbar>
    </x-slot:selection>
</x-ui::data-table>
```

自定义单元格在表格的默认插槽中遍历 `$component->records`。这是表格已读取的当页记录，数组、集合、分页器和只能遍历一次的 Generator 使用相同写法。

| 配置 | 调用约定 |
| --- | --- |
| 列定义 | 数组键是稳定列键，`label` 是标题。`field` 默认取列键，显式传 `null` 则默认留空。普通字段转义输出。自定义单元格即使内容为空也优先使用。`align` 同时控制表头和数据的对齐，可取 `start`、`center`、`end`，金额等数值适合使用 `end` |
| 列布局 | `layout-model` 绑定页面的 `['order' => [], 'hidden' => []]`。普通列默认可以隐藏和移动，`hideable` 和 `reorderable` 可分别关闭。`fixed` 支持 `start`、`end`，同侧多列按实际宽度贴边。列面板用复选框控制可见列，拖动把手可调整顺序，也可聚焦把手后使用上下方向键。固定列标明位置。「恢复默认」把页面的临时布局恢复为空数组配置。页面每次只传已授权的列 |
| 数据排序 | `sort-model` 与列的 `sortable` 一起启用表头菜单。值为 `null` 或 `['column' => 'number', 'direction' => 'asc']`，降序使用 `desc`。页面将允许排序的列键映射到查询 |
| 筛选 | `primary` 常显，`secondary` 默认平铺。开启 `collapsible` 后默认收起次区，`initially-collapsed` 控制初始状态。`clear-action` 指定页面的清空方法 |
| 多选 | 根组件的 `wire:model.live` 绑定 ID 数组。新增选择使用原生复选框的字符串键。`row-selectable` 接受页面回调，表头全选只更新本页可选行。默认工具栏在有选择时显示计数和清除按钮，`placement="viewport"` 可固定在视口底部 |
| 总数与分页 | 传 Laravel 分页结果时默认显示页脚。完整分页复用 `total()`，`data-table.total` 也可用 `:value` 传整数或完整集合。简单和游标分页提供上一页、下一页。默认页脚与自定义页脚共用基础 `pagination` 的 `max-pages` 和 `jumpable` |
| 每页条数 | `page-size` 复用 Select，默认 10、25、50。`options` 接受值和标签数组，如 `[['value' => 25, 'label' => '25']]`。页面校验条数，使用 `WithPagination` 和该分页器的页名重置页码 |

页面在搜索或筛选变化时清空选择、回到第一页，在排序或每页条数变化时回到第一页并保留选择。批量操作按当前授权范围重新核对所选 ID。同一页面的多张表使用独立的绑定和分页名。[数据表格公开调用](components.md#data-table)

## 空数据状态

Select（含 `data-table.page-size`）、Combobox、Command、TreeSelect、Cascader、Mentions、Transfer 和 Steps 使用 `<x-ui::empty size="sm">` 展示标题和可选说明。`empty-title` 与 `empty-description` 接受文本，父级更新后由 Blade 重新渲染。省略文案时使用当前语言的默认提示，传 `empty-description=""` 可以省略默认的搜索建议。

```blade
<x-ui::select wire:model="role" :options="$roles" placeholder="请选择角色"
    empty-title="暂无可选角色" empty-description="可以先在团队设置中添加角色。" />
```

选项存在时，尚未选择的字段继续显示占位文字。搜索无匹配时提示更换关键词。Mentions 在有效的 `@` 查询期间显示候选或空提示。Transfer 的两栏分别计算结果，区分尚未选入、已全部选入和该栏的搜索无匹配。

Tree 与 Questionnaire 的主体为空时使用默认尺寸的 `empty`，也接受上述两个文案属性。要放入“创建根目录”等业务操作，页面在自身的 Blade 空分支中组合 `empty.header`、`empty.media` 和 `empty.content`，由页面的 Livewire 方法处理按钮。

DataTable 根据当前可见列数设置 `table.empty` 的跨度，保留表头和列设置。使用 `<x-slot:empty>` 可提供提示和页面操作。基础 Table 由页面在 `@forelse` 的空分支中显式使用 `table.empty`，并按当前可见列数设置 `colspan`。

```blade
@forelse ($rows as $row)
    <x-ui::table.row wire:key="project-{{ $row->id }}">
        <x-ui::table.cell>{{ $row->name }}</x-ui::table.cell>
        <x-ui::table.cell>{{ $row->status }}</x-ui::table.cell>
    </x-ui::table.row>
@empty
    <x-ui::table.empty colspan="2">
        <x-ui::empty>
            <x-ui::empty.header>
                <x-ui::empty.title>没有符合条件的项目</x-ui::empty.title>
                <x-ui::empty.description>试试其他关键词，或清除当前筛选。</x-ui::empty.description>
            </x-ui::empty.header>
            <x-ui::empty.content>
                <x-ui::button wire:click="clearFilters">清除筛选</x-ui::button>
            </x-ui::empty.content>
        </x-ui::empty>
    </x-ui::table.empty>
@endforelse
```

Item Group、Attachment Group、Timeline、Trace、Message Scroller 和 Carousel 的集合归提供 slot 的页面所有。页面根据原始集合渲染列表或一个完整的 `empty`。Radio Group、Toggle Group 的选项由页面提供，选项集合为空时在字段内组合紧凑提示。Native Select 使用原生选项。

## 当前交互模式

Popover 中可以用 Tab 和 Shift+Tab 按页面顺序访问按钮和输入框。焦点移出时关闭弹层，Escape 关闭最内层并把焦点还给其触发控件。菜单和列表框支持方向键导航。

Cascader、Tree Select、Select 和 Combobox 的弹层默认贴着触发控件下缘展开，起始边对齐。Combobox 以整个输入组或外部触发按钮定位。父容器移动、滚动或 Livewire 重绘时，打开的弹层继续跟随控件。触发控件完全滚出可见区域后，弹层自动关闭。给 Select 或 Combobox 传入 `:align-item-with-trigger="true"`，可让选中项与触发控件的垂直中心对齐。Combobox 支持输入框内搜索和弹层内搜索，触摸打开或搜索结果为空时沿用普通弹层定位。

Context Menu 在触发 slot 收到右键时打开，位置取自点击点相对于触发控件的偏移。靠近窗口边缘时菜单向有空间的一侧展开。键盘用户可聚焦触发控件并按 Shift+F10 或菜单键，Escape 关闭后焦点回到该控件。

Sheet / Drawer 的 content 支持 side=top/right/bottom/left，默认分别为 right/bottom。Popover / Tooltip / HoverCard / 菜单 content 支持 side=top/right/bottom/left 和 align=start/center/end，空间不足时自动翻转和约束到窗口内。弹层打开时跟随内容尺寸及祖先滚动更新位置，关闭后释放监听。

Tabs 支持 orientation=horizontal/vertical，分别使用左右/上下方向键；选项值统一为字符串。ToggleGroup 支持 type=single/multiple，item 分别输出原生 radio/checkbox，绑定写在 item 上；同组共享 name，多值表单使用 name[]。组级 variant=default/outline、size=sm/default/lg 和 disabled 传递到 item；orientation=horizontal/vertical，spacing=0–12（每单位 .25rem）。Tabs.list 支持 variant=default/line。


Slider 的值为数字或非空数字列表（最多 20 项），每个值必须在 min/max 内。支持 orientation、inverted 和 step。绑定时由调用方 Livewire 保存权威值，单向受控模式在拖动结束后请求父级接受。非受控模式下，即使父级传入不同长度的 `initial-value`，同一实例也会保留手柄数量、已编辑的值和表单重置初值。更换 `wire:key` 可按新的初值重新挂载。

InputOtp 使用同一值协议，值为字符串；length=1–12，pattern=numeric/alphanumeric，separator-after=0 表示无分隔符，其他值表示指定格后的分组间隔。支持粘贴过滤、前后方向键、自动移焦、只读/禁用；公开 slot 仍由父级管理。

Sidebar 使用 collapsible=offcanvas/icon/none、variant=sidebar/floating/inset、side=left/right。桌面显示不抢焦点；移动端使用原生 modal 和焦点恢复。默认展开状态由 initial-open 决定，x-modelable 可对接调用方开关。内容区放在 sidebar.inset，触发控件通常置于该内容区内。

侧栏可滚动导航区使用 `sidebar.body`。`sidebar.menu-button` 和 `sidebar.menu-link` 共用 size、variant、active；可用 `tooltip` 提供折叠为图标时的文字提示，内部组合已有 Tooltip，提示仅在桌面图标折叠状态启用。Tooltip 触发器可以与 Collapsible 触发器嵌套在同一控件上，各自保留弹层与展开行为。

可折叠 Sidebar 桌面默认固定到视口边缘，使用 sidebar-gap 为主内容预留位置；floating 模式在容器内保留 8px 留白，折叠容器宽 66px。嵌入式演示可在 sidebar 根上使用 `h-56 [transform:translateZ(0)]`、在 sidebar.content 上使用 `md:h-full` 限定展示区域。移动端仍使用顶层弹层。纵向 Carousel 的可视高度由调用方通过 carousel.content 的 `h-*` 类指定，与官方纵向示例一致。

菜单的 checkbox-item/radio-item 输出原生控件，wire:model 写在相应 item 上，close-on-select 可控制选择后是否关闭。radio-group 与 sub/sub-trigger/sub-content 提供组合。sub-trigger 依然只绑定行为，按钮及箭头图标由调用方 slot 提供。

HoverCard / Tooltip / NavigationMenu 的 open-delay、close-delay 控制指针开关延迟，HoverCard / Tooltip 的焦点触发立即打开；延迟使指针能穿过触发器与内容间隙。Drawer 有内置拖动手柄，Escape/close 和拖动关闭共享同一状态与滚动锁。

Questionnaire 的输入、导航、事件与组合约定见 [问卷](questionnaire.md)。`@island` 与嵌套 Livewire 的区别及本机开销见 [状态边界评估](state-boundaries.md)。

Carousel 使用 `align="start|center|end"`（默认 center）与 `loop` 配置同一 Embla 引擎；Alpine 只管理滚动、拖动、方向键和边界，条目继续由 Blade 输出，父级 Livewire 增删条目由引擎重新测量。

Toaster 通过 `limit`（默认 3）控制可见堆叠，调用方按新通知在前的顺序用 Blade 输出带稳定 `wire:key` 的 Toast。悬停/键盘焦点展开堆叠并暂停计时，窗口失焦时也暂停，恢复后按剩余时间继续；F6 进入通知区，Tab 导航可见通知和操作，Escape 关闭当前通知并转移焦点，超出 limit 的通知不进入焦点顺序；Toast 支持拖动回弹/关闭与进入/退出动画，`duration="0"` 不自动关闭。Livewire 更新 duration 后重新开始相应计时。通知内容、异步任务结果和 Undo 等操作仍由调用方所属 Livewire 渲染/执行，close 子组件只包装调用方的按钮。
