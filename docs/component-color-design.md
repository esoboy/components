# 组件状态色与样式接口设计

组件样式以根 class 和 CSS 变量为公开接口。以下示例适用于当前实现。

## 与旧版 CSS 的区别

| 关注点 | 迁移前 | 当前实现 |
| --- | --- | --- |
| 样式入口 | 按钮已有 `.ui-button`，徽标、开关滑块等主要用 `[data-slot=…]:where([data-esoboy-ui])` 定位。 | 每个组件提供稳定的根 class，例如 `.ui-button`、`.ui-badge`、`.ui-switch`。导入库 CSS 后，在普通 HTML 页面即可生效。 |
| 变体渲染 | 徽标模板输出 `data-variant`，CSS 按属性切换外观。按钮模板已经会输出固定的变体类。 | Blade 按 `variant` 渲染基础结构和外观类，变体样式给出布局、边框、默认配色和交互反馈。有专属部位的变体只输出自己需要的节点。 |
| 颜色来源 | 变体 CSS 直接使用 `bg-primary`、`text-foreground`、`border-border` 等主题工具类。 | 各变体自带完整配色。显式指定 `status` 时，组件通过部位变量替换相应颜色。 |
| 实例覆盖 | 应用可以用工具类覆盖样式，也可以改组件读取的通用主题变量。某些内部部位需要单独定位。 | 应用在根 `class` 里改一个部位变量即可，例如 `[--ui-button-border:var(--color-violet-600)]` 只改这个按钮的边框。 |

宿主布局只需引入组件 CSS。应用中依赖旧 DOM 的选择器可将 `[data-slot="badge"]` 换成 `.ui-badge`，描边变体使用 `.ui-badge-variant-outline`。

## 调用方式

`variant` 决定组件的基础结构和样式，单独使用就有完整外观。`status` 是可选的颜色覆盖，没有默认状态。Blade 仅在显式收到五个状态之一时追加对应的 `ui-status-*` 类，省略、传 `null` 或空字符串均不追加。应用的 `class` 合并到组件根节点，可覆盖单个部位的最终颜色。

当前由 `button`（含链接和 `button.text`）、`badge`、`switch`、`alert` 和 `toast` 接收 `status`。其余组件同样公开部位颜色变量。

```blade
<x-ui::button variant="outline" status="info">查看详情</x-ui::button>
<x-ui::badge variant="secondary" status="neutral">草稿</x-ui::badge>
<x-ui::badge variant="secondary" status="info">正在同步</x-ui::badge>
<x-ui::switch status="success" wire:model="enabled" aria-label="启用同步" />

<x-ui::button variant="outline" status="info"
    class="[--ui-button-border:var(--color-violet-600)]">查看详情</x-ui::button>

<x-ui::progress :value="72" aria-label="上传进度"
    class="[--ui-progress-track:var(--color-slate-200)] [--ui-progress-fill:var(--color-info)]" />
```

同一个 `secondary` 徽标，省略状态和指定 `info` 时分别生成以下 HTML。两者的节点和变体完全相同。

```html
<span class="ui-badge ui-badge-variant-secondary">草稿</span>
<span class="ui-badge ui-badge-variant-secondary ui-status-info">正在同步</span>
```

普通主按钮省略 `status` 时取变体原有的 `primary`，显式写 `status="neutral"` 才给它灰色。`secondary` 按钮本身已有灰色，省略状态就能得到这个外观。`destructive` 按钮同理，单独使用沿用原有配色，搭配 `status="info"` 时保留淡底形态，改取信息色。开关的开闭、控件的禁用和通知的 ARIA 角色继续由原有接口决定。

## 五种状态色

状态名描述业务含义。`neutral` 用于平实地标明现状，`info` 用于主动告知一条消息。`secondary` 保留为外观名，`accent` 继续作为主题强调色，通过现有工具类或组件部位变量使用。这样组件库仍提供 `accent`，`status` 的取值共五个。警示状态统一拼为 `warning`。

每种状态提供三种用途的颜色。下表每格依次为实心底色、实心底上的前景色、普通表面上的强调文字色。后者用于淡底或描边，例如白底上的警示文字用深琥珀色。

| 状态 | 业务示例 | 浅色默认值 | 暗色默认值 |
| --- | --- | --- | --- |
| `neutral`（灰） | 草稿、已归档 | `secondary` / `secondary-foreground` / `secondary-foreground` | 同一组变量，随暗色主题切换 |
| `info`（蓝） | 正在同步、有新版本 | `sky-700` / 白 / `sky-800` | `sky-400` / `sky-950` / `sky-300` |
| `success`（绿） | 已完成、支付成功 | `emerald-700` / 白 / `emerald-800` | `emerald-400` / `emerald-950` / `emerald-300` |
| `warning`（琥珀） | 即将到期、额度不足 | `amber-400` / `amber-950` / `amber-800` | `amber-300` / `amber-950` / `amber-200` |
| `error`（红） | 保存失败、连接出错 | `oklch(0.577 0.245 27.325)` / 白 / `red-700` | `oklch(0.704 0.191 22.216)` / `#171717` / `red-300` |

`neutral` 直接引用按钮使用的 `--color-secondary` 和 `--color-secondary-foreground`。浅色主题里的 `secondary` 和 `accent` 恰好同色，暗色主题里的底色分别为 `oklch(0.269 0 0)` 和 `oklch(0.371 0 0)`。应用定制 `secondary` 时，灰色状态随之更新。

四种彩色状态各自公开 `--color-{status}`、`--color-{status}-foreground`、`--color-{status}-ink`。`--color-error` 在亮暗主题中独立声明，初始色值取各主题的 `destructive` 色值。应用可以分别定制错误提示和破坏性操作。全局配色沿用 Tailwind 的 `@theme` 和库已有的 `.dark` 规则。

## 变体怎样使用状态色

| 外观 | 绘制规则 |
| --- | --- |
| 实心按钮、徽标 | 背景取状态底色，文字与单色图标取配套前景，悬停沿用该变体的反馈。 |
| `secondary` 或淡底 | 彩色状态用淡底与对应的 `ink`。`neutral` 直接用已有 `secondary` 底色与前景，保留灰阶。灰色 `secondary` 按钮的常态、悬停和展开效果与现有按钮一致。 |
| `outline` | 使用描边外观，边线与文字取可辨识的状态强调色，悬停可出现同色淡底。 |
| `ghost`、`link` | 使用状态强调文字色。悬停反馈分别沿用淡底或下划线。 |
| 开关 | 开启轨道取状态底色，滑块取配套前景。关闭轨道沿用中性色，通过滑块位置也能区分开闭。 |
| 提示、通知 | 图标和边框表达状态。正文、次要说明分别使用承载表面的文字色，保留阅读层次。 |

Blade 从五个状态名中选取一个固定 class，例如 `ui-status-info`。每种状态只需配好实心底色、实心前景和强调文字。彩色淡底由底色统一生成，灰色淡底直接取 `secondary`。变体所渲染的部位读取各自需要的颜色。把 `info` 换成 `success` 时，节点结构、交互和 ARIA 语义一致。

## 每个组件的颜色变量

每个组件按可独立着色的部位公开 `--ui-组件-部位`。跟随文字的单色图标使用 `currentColor`。需要区分的部位才单设变量，例如提示图标与正文、开关轨道与滑块。悬停文字先读取 `--ui-button-hover-fg`，再回退到 `--ui-button-fg`，应用改一次前景即可覆盖两态。下面列出常用部位。组件根上可覆盖的变量按组件名列在[部位变量清单](../resources/css/components/part-colors.css)。

| 组件 | 部位变量示例 |
| --- | --- |
| `button`、`badge` | 各自前缀下的 `bg`、`fg`、`border`、`hover-bg`、`hover-fg`。按钮另有 `focus-ring`。 |
| `switch` | `--ui-switch-track-on`、`--ui-switch-track-off`、`--ui-switch-thumb-on`、`--ui-switch-thumb-off`、`--ui-switch-focus-ring` |
| `alert`、`toast` | 各自前缀下的 `bg`、`border`、`icon`、`title-fg`、`description-fg` |
| `progress` | `--ui-progress-track`、`--ui-progress-fill`，原生进度条的浏览器伪元素读取相同的变量。 |
| `select` | `--ui-select-trigger-bg`、`--ui-select-panel-bg`、`--ui-select-option-selected-bg`、`--ui-select-option-selected-fg` |
| `password-input` | `--ui-password-input-value-fg` 用于输入值，`--ui-password-input-placeholder-fg` 用于占位文字。 |
| `signature` | `--ui-signature-ink` 用于画布笔迹，`--ui-signature-surface-bg` 和 `--ui-signature-surface-border` 分别用于画布底色和边框。 |

组件在实例根元素上用 Tailwind 的 `components` 层声明默认部位变量，绘制节点读取这些变量。应用的 `utilities` 层可以直接覆盖最终值。例如示例中的按钮只替换边框，文字与悬停仍走 `outline + info`。同类组件嵌套时，子实例在自己的根上重新声明默认值。全局主题色可以跨实例继承。

实例 `class` 应落在能覆盖全部绘制部位的根上。例如 `date-picker` 的触发器与弹层目标位于同一个实例容器内，使一处变量覆盖同时作用于两边。原生输入继续接收 `id`、`name`、`wire:model` 和 ARIA 属性。应用原先直接针对触发器的焦点样式可改用部位变量，容器级反馈可在根上使用 `focus-within:`。

## 根 class 与 Blade 渲染

组件把自己的根 class 加到原有根节点上。比如 `button` 的操作模式渲染原生 `<button>`，传入 `link` 的导航模式渲染 `<a>`，两者共享 `ui-button`。实心和描边按钮可以共用同一棵 DOM，由 Blade 输出对应的外观类。下面是描边信息按钮的渲染示意。

```html
<button type="button" class="ui-button ui-button-variant-outline ui-status-info">查看详情</button>
```

变体有额外内容或交互区域时，Blade 直接渲染相应节点。仅有装饰差异时，共用节点就够了。例如 `marker` 的 `separator` 由 Blade 输出 `ui-marker-variant-separator`，沿用现有的 `::before` 和 `::after` 绘制两条线，二者都读取 `--ui-marker-line`。

### 同一个描边徽标的 CSS 对照

迁移前的描边、文字与悬停配色集中在一条变体规则中。

```css
[data-slot='badge']:where([data-esoboy-ui])[data-variant='outline'] {
    @apply border-border text-foreground [a]:hover:bg-muted [a]:hover:text-muted-foreground;
}
```

现在，Blade 把组件类、变体类和显式状态类合并在同一个根节点。以下是描边徽标常态的两条绘制声明。应用的部位变量优先，随后取显式状态的强调色，最后回退到变体的主题色。

```css
.ui-badge.ui-badge-variant-outline {
    border-color: var(--ui-badge-border, var(--ui-status-ink, var(--color-border)));
    color: var(--ui-badge-fg, var(--ui-status-ink, var(--color-foreground)));
}
```

在组件层，实例根将本组件的部位变量和内部状态变量重置为空。同类组件嵌套时，子实例使用自己的变体配色。应用可以在根上用 Tailwind 任意属性覆盖部位，普通 CSS 也可以通过同名变量覆盖。主题变量沿 DOM 继承。[状态配色](../resources/css/components/status.css) · [主题色值](../resources/css/tokens.css) · [组件样式](../resources/css/base-ui.css)

## 旧选择器迁移

| 旧定位方式 | 当前定位方式 |
| --- | --- |
| 按组件的 `data-slot` 和 `data-esoboy-ui` 查找 | 使用组件根类，例如 `.ui-button`、`.ui-badge`、`.ui-select` |
| 用 `data-variant="outline"` 匹配按钮 | 使用 `.ui-button-variant-outline` |
| 在 select 或日期选择器的按钮上读取应用 class | 应用 class 在组件根上，按钮分别使用 `.ui-select-control` 和 `.ui-date-picker-trigger` |
| 表格和原生选择框本身 | 容器分别使用 `.ui-table` 和 `.ui-native-select`，原生节点使用 `table.ui-table-element` 和 `select.ui-native-select-control` |

行为查询仍可使用原生角色、ARIA 与组件已有的交互标记。应用自己在插槽中使用的 `data-slot` 由应用管理。
