# Base UI 对照与组件能力

基线为 shadcn 官网 Components 的 **Base UI / Nova** 示例，固定提交 `e62c9435fff729a175f38983dee73b2cb4f6a069`（2026-11-13）。发行包的 `THIRD-PARTY-LICENSES.txt` 包含这份上游许可。[官方源码](https://github.com/shadcn-ui/ui/tree/e62c9435fff729a175f38983dee73b2cb4f6a069/apps/v4/registry/bases/base/ui) · [Nova 样式](https://github.com/shadcn-ui/ui/blob/e62c9435fff729a175f38983dee73b2cb4f6a069/apps/v4/registry/styles/style-nova.css) · [随源码保存的许可](../resources/css/LICENSE.shadcn)

本库保留 Blade 渲染、Livewire 4 数据和 Alpine 浏览器交互边界。React Context、render/asChild、客户端渲染列表、Portal 组件不直接移植为公开 API；对应内容组合使用 props / slot，浮层使用原生 top layer。下表列出本库对应入口与模式；实际调用见 components.md，完整模式在演示应用 `/components/{name}`。

| 组件 | 当前能力与对齐重点 |
| --- | --- |
| accordion | 单项/多项展开、collapsible、disabled、方向键/Home/End；原生 details/summary |
| alert | default/destructive、图标、标题、说明和 action |
| alert-dialog | 默认/小尺寸、media、标题/说明、行为 trigger/close；确认动作由调用方实现 |
| attachment | 横向/纵向、大小、media、文件名、描述、actions、group |
| avatar | sm/default/lg、图片回退、badge、group/count |
| badge | default/secondary/outline/destructive/ghost/link 外观 |
| breadcrumb | list/item/link/page/separator/ellipsis |
| bubble | 七种 variant、start/end、group、reactions 的 side/align |
| button | 六种 variant、八种 size、disabled、link URL；大尺寸 36px |
| button-group | 横向/纵向、text、separator；原生按钮组合 |
| calendar | 单选、多选、范围、1–12 月、月份选择、周起始、周数、边界/禁用日期、键盘 |
| card | default/sm、header/title/description/action/content/footer |
| carousel | 横向/纵向、align、loop、逐项吸附、前后边界、方向键与拖动惯性；复用 Embla 8.5.2，减少动画偏好下保持官方引擎表现 |
| checkbox | checked/indeterminate/invalid/disabled、原生 wire:model |
| code-block | default/destructive、default/sm、语言/文件名、复制、行号/起始行、换行、header/footer slot |
| collapsible | 原生 details/summary，open 初值与任意 Blade 内容 |
| combobox | 单/多选、服务端搜索、空/禁用/只读、输入框内的选择 chips/删除、clearable、分组、键盘 |
| command | 服务端搜索与分组选择、快捷键提示、键盘、空状态；可放入 Dialog 组成命令面板 |
| context-menu | 指针/键盘打开、checkbox/radio、嵌套子菜单、shortcut、方向/对齐 |
| data-table | 通过基础 Table 与 Pagination 组成列表。列设置支持拖拽及多选显隐。调用方 Livewire 管理查询、排序、筛选、分页、选行和业务动作 |
| date-picker | Calendar 的单选、多选、范围与多月，年月标题下拉、clearable 和弹层 extra slot |
| dialog | 原生 modal、标题/说明、焦点、Escape、遮罩、嵌套滚动锁、调用方 trigger/close |
| drawer | 四方向、可选 show-swipe-handle、焦点、滚动锁及原生表层过渡 |
| dropdown-menu | checkbox/radio、嵌套子菜单、shortcut、类型搜索、方向/对齐 |
| empty | header/media/title/description/content、default/icon media、默认尺寸的完整空状态与 `size="sm"` 紧凑提示 |
| field | set/legend/group/content/label/title/description/separator/error；纵向/横向/响应式 |
| hover-card | hover/focus、open-delay/close-delay、可进入内容区、方向/对齐 |
| input | 原生输入类型与字段属性、invalid、disabled、readonly |
| input-group | input/textarea/control、四种 addon align、button/text、组合焦点/校验 |
| input-otp | 独立格、长度、numeric/alphanumeric、separator-after、粘贴/过滤/自动焦点、三种值模式 |
| item | default/outline/muted、default/sm/xs、media/content/actions/header/footer/group/separator |
| kbd | 单键与组合键 |
| label | 原生字段关联 |
| marker | default/separator/border，icon/content；用于内容标记和分隔 |
| menubar | 横向菜单切换、checkbox/radio、子菜单、shortcut、方向/对齐 |
| message | avatar/content/header/footer/group |
| message-scroller | 跟随最新消息、手动滚动暂停跟随、开始/末尾按钮、内容变化与尺寸监听释放 |
| native-select | 原生 select/option/optgroup、多选及字段属性 |
| navigation-menu | link、菜单弹层、hover/点击/键盘、方向/对齐，内容由 Blade 组合 |
| pagination | link/previous/next/ellipsis。根组件通过 `:paginator` 接入 Laravel 分页，`max-pages` 控制窗口，`jumpable` 开启手动跳页 |
| popover | 自由内容、trigger/close、定位/翻转、焦点/键盘 |
| progress | 确定/不确定进度、max、label/value，原生 progress 可访问语义 |
| questionnaire | 单选/多选/自由输入、显式跳过、草稿、校验、受控导航、快捷键、原生表单、重置；一个 Livewire 实例 |
| radio-group | 原生 radio、checked/invalid/disabled、父级 wire:model |
| resizable | 横向/纵向、多个/嵌套面板、初始比例、鼠标与键盘；相邻面板调整保持其他面板比例 |
| scroll-area | 原生 viewport、键盘、Nova 滚动条、横纵方向与拖动 |
| select | 单/多选、占位/空/禁用/只读、选项分组/键盘、隐藏表单值 |
| separator | horizontal/vertical |
| sheet | 四方向、焦点、遮罩与滚动锁 |
| sidebar | offcanvas/icon/none、sidebar/floating/inset、left/right、移动模态、menu/sub/actions/badge |
| skeleton | 语义颜色与动画；调用方控制布局 |
| slider | 数字/数字列表、min/max/step、横向/纵向、反向、拖动/键盘、只读/受控 |
| spinner | 可访问加载语义与尺寸 |
| switch | default/sm、checked/disabled/invalid、原生 wire:model |
| table | 语义表格标签、行样式和插槽。页面手写表头、数据、合并单元格与 `table.empty` 空行 |
| tabs | horizontal/vertical、default/line、键盘、父级刷新保留当前项 |
| textarea | 原生字段属性、invalid/disabled/readonly |
| toast | 调用方消息数据、标题/说明/actions、自动隐藏、悬停暂停、位置由 Toaster 控制 |
| toggle | default/outline、sm/default/lg、pressed/disabled、原生 checkbox |
| toggle-group | single/multiple、variant/size/disabled、横向/纵向、spacing、键盘 |
| tooltip | hover/focus、延迟、Escape、方向/对齐与可访问描述 |
| trace | 用 `trace.line` 组合 icon、title 和 description。完成时省略 status，其余可选 running、error 和 pending。将 `trace.collapsible-button` 放入 `trace.collapsible-trigger` 的 slot 可使用带展开箭头的标准按钮，也可提供自定义控件。 |

视觉基准来自固定 Nova 源码。本轮完成 298 组同内容用例（62 个根组件）的浅色/深色、桌面/移动截图对照，并覆盖 56 组控件的 hover、focus-visible、active；具体结果、截图栅格条件和验收范围见 [shadcn 组件覆盖](shadcn-coverage.md)。

## 本库扩展

公开根组件的数量和归类见[组件覆盖统计](shadcn-coverage.md#统计)。

- 已明确保留：editor、rich-text；按共享内容契约实现。
- 建议保留：image、media-preview、number-input、password-input、time-picker、datetime-picker、tree、tree-select、tags-input、color-picker、qr-code、cropper、steps、timeline、description-list、statistic。
- 继续保留、由业务决定是否收敛：cascader、transfer、mentions、signature、rating、tour。

移除 verification-code-button、link、float-button、back-to-top、aspect-ratio、emoji-picker、confirm、anchor、countdown、watermark、result。原 confirm 调用迁移为 AlertDialog。button/text 通过 link 属性提供链接。独立 mentions 的保留不代表 Editor 支持 Mention 节点。

Typography 仅由 heading/text 部分承接，未实现官方 Typeset 系统。Chart、Direction 是新增候选，未擅自加入；AspectRatio 继续移除；Questionnaire 按本轮明确要求重新加入。额外设计取舍记录在仓库任务目录的 design-recommendations.md，未经确认不实施。
