# Questionnaire

按照固定 shadcn Base UI / Nova 的 Questionnaire 实现。题目由 Blade 渲染，答案、校验状态和题目导航由一个内部 Livewire 组件管理；每道题不是独立实例。Alpine 只处理键盘与焦点。根元素为 form，不能放入另一个 form。

## 调用

```blade
<div x-on:questionnaire-submitted="$wire.saveAnswers($event.detail.answers)">
    <x-ui::questionnaire id="onboarding" wire:model="answers"
        :items="$questions" shortcuts="numbers" submit-label="保存" />
</div>
```

事件监听放在调用方的 Blade 容器中，业务保存属于该容器的 Livewire。它会收到 `questionnaire-submitted` 的 answers，但组件不保存数据、不请求业务 API。绑定和业务事件均已通过同一个实际页面验证。

```php
$questions = [
    [
        'name' => 'scope', 'prompt' => '需要完成什么？', 'required' => true,
        'description' => '选择一项，或填写自己的答案。',
        'choices' => [
            ['value' => 'ui', 'label' => '界面', 'description' => '样式和交互'],
            ['value' => 'docs', 'label' => '文档'],
        ],
        'input' => ['label' => '其他任务', 'placeholder' => '填写其他任务…'],
    ],
    [
        'name' => 'checks', 'prompt' => '需要哪些验收？', 'multiple' => true,
        'choices' => [['value' => 'php', 'label' => 'PHP'], ['value' => 'browser', 'label' => '浏览器']],
    ],
    ['name' => 'notes', 'prompt' => '补充说明', 'input' => ['label' => '说明']],
];
```

题目 name 必须唯一，由字母开头并只包含字母、数字、下划线或连字符；选项 value 为题内唯一字符串。题目/选项支持 disabled；禁用题目不计入进度、导航、校验和提交。`items[].error` 可提供项目验证后的错误文本；有错误时不能通过验证。

## 状态与导航

答案使用关联数组，每题包含 `choices`（字符串列表）、`text`（自由输入）、`status`（unanswered / answered / skipped）。单选中输入非空文本会清除固定选项；选择固定选项会清除文本。多选允许文本和多个选项并存。

```php
[
    'scope' => ['choices' => ['ui'], 'text' => '', 'status' => 'answered'],
    'checks' => ['choices' => [], 'text' => '', 'status' => 'skipped'],
]
```

- `wire:model` / `value` / `initial-value` 三选一，遵循库的公共值协议；readonly、disabled 在服务端操作入口同样生效。
- `initial-item` 指定初始题名，默认首个启用题目。`item` 非 null 时由调用方控制当前题；监听冒泡 `item-changed` 的 `detail.item` 后决定是否接受。
- `shortcuts` 可为 letters / numbers / 空字符串。字母 A–Z、数字 1–9，禁用选项跳过，选择不会自动进入下一题。
- 上一步保留答案；下一步验证当前题，提交验证所有启用题。可选题也需要回答或明确跳过；跳过会清除该题答案。
- `submit-label` 默认使用语言包。默认 slot 位于内部 form 之后，业务按钮仍归调用方，不能当作内部导航按钮插槽。
- 原生 `form.reset()` 恢复初始答案并清除校验状态。答案受控时，由父级决定是否接受恢复初值的请求。题目受控时，`item-changed` 请求切换到 `initial-item`，该题缺失或已禁用时请求首个启用题目。父级更新 `item` 后，界面切换到目标题。

控件使用 fieldset / legend / 原生 radio、checkbox、input，进度包含 progressbar 语义。方向键上下移动答案；非输入区域的左右键导航；Enter 继续；Ctrl/Command+Enter 校验并继续或提交。非空文本输入时暂停选项快捷键。导航聚焦新题，验证失败聚焦第一个可用答案。

原生 `FormData` 按题目 `name` 取值。`get` 读取单项，`getAll` 读取多选值和自由文本。只读问卷在任意题目上提交时，都包含全部启用且已作答的题目。已跳过、禁用题不提供字段。Livewire 保存通常直接使用标准化 answers。项目负责授权、业务规则与持久化。

## Blade 组合部件

提供 progress、item、title、description、choices、choice、choice-description、input、error、actions、previous、next、skip、submit。视觉部件可用于项目自有流程的 Blade 布局。previous / next / skip / submit 是只提供行为的 slot 包装，不生成默认按钮；内部默认布局以 Button 填充这些 slot。项目自有流程使用这些行为部件时，所属 Livewire 提供同名动作。

`layout` 可为 `standard`、`card` 或 `dialog`。`dialog` 布局复用 Dialog Header / Footer，适合将问卷放入已有 Dialog Content；`progress-class` 定制进度文字样式，`show-progress` 控制是否展示。进度文字使用 progressbar、aria-valuenow 和 aria-valuetext 表达当前题目，答案和题目内容仍由同一个 Livewire 实例经 Blade 更新。

完整模式见演示 `/components/questionnaire`，公共绑定/提交/slot 场景见 `/scenarios/questionnaire`。本次重建以当前 API 为准，不兼容之前移除的 Questionnaire 实现。
