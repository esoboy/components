# shadcn 与 Esoboy UI 组件对照

目录核查：2026-11-17；验收更新：2026-11-18。以 [shadcn 当前 Components 目录](https://ui.shadcn.com/docs/components)为官网清单，以当前工作区真实公开 Blade 根组件为本库清单，并与 `examples.json`、应用 `config/examples.php` 交叉核验。子组件不重复算根组件；AI Elements、社区 Registry、Forms 集成教程不纳入 Components 目录统计。以下“存在”仅表示有对应组件入口，不表示 API、全部能力或 UI 完全一致。

## 统计

| 统计口径 | 数量 |
| --- | ---: |
| shadcn Components 目录项 | 66 |
| 本库公开根组件 | 90 |
| shadcn 有直接对应入口 | 62 |
| shadcn 部分对应 | 1（Typography） |
| shadcn 缺失 | 3（Aspect Ratio、Chart、Direction） |
| 本库扩展根组件 | 24 |
| 本库基础封装根组件 | 4（heading、text、toaster、icon） |

## 官网完整对应表

下表逐项对应 [官网目录](https://ui.shadcn.com/docs/components)。组件存在性由当前源码核实。

| shadcn 组件 | 本库公开入口（前缀 x-ui::） | 状态 |
| --- | --- | --- |
| Accordion | [accordion](../resources/views/components/accordion/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Alert | [alert](../resources/views/components/alert/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 4 组通过 |
| Alert Dialog | [alert-dialog](../resources/views/components/alert-dialog/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 6 组通过 |
| Aspect Ratio | — | 不存在 |
| Attachment | [attachment](../resources/views/components/attachment/index.blade.php) | 已实现 Nova；基础 6 组通过；官方实际示例 8 组通过 |
| Avatar | [avatar](../resources/views/components/avatar/index.blade.php) | 已实现 Nova；基础 7 组通过；官方实际示例 7 组通过 |
| Badge | [badge](../resources/views/components/badge/index.blade.php) | 已实现 Nova；基础 6 组通过；官方实际示例 7 组通过 |
| Breadcrumb | [breadcrumb](../resources/views/components/breadcrumb/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 3 组通过 |
| Bubble | [bubble](../resources/views/components/bubble/index.blade.php) | 已实现 Nova；基础 7 组通过；官方实际示例 8 组通过 |
| Button | [button](../resources/views/components/button.blade.php) | 已实现 Nova；基础 50 组通过；官方实际示例 6 组通过 |
| Button Group | [button-group](../resources/views/components/button-group/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 17 组通过 |
| Calendar | [calendar](../resources/views/components/calendar/index.blade.php) | 已实现 Nova；基础 3 组通过；官方实际示例 14 组通过 |
| Card | [card](../resources/views/components/card/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 12 组通过 |
| Carousel | [carousel](../resources/views/components/carousel/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 3 组通过 |
| Chart | — | 不存在 |
| Checkbox | [checkbox](../resources/views/components/checkbox.blade.php) | 已实现 Nova；基础 7 组通过；官方实际示例 7 组通过 |
| Code Block | [code-block](../resources/views/components/code-block/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 30 组通过 |
| Collapsible | [collapsible](../resources/views/components/collapsible/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 2 组通过 |
| Combobox | [combobox](../resources/views/components/combobox/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 20 组通过 |
| Command | [command](../resources/views/components/command/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Context Menu | [context-menu](../resources/views/components/context-menu/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 11 组通过 |
| Data Table | [data-table](../resources/views/components/data-table/index.blade.php) | 使用 Nova 的 Table、Checkbox、Button、Popover 与 Pagination 组成后台列表 |
| Date Picker | [date-picker](../resources/views/components/date-picker/index.blade.php) | 已实现 Nova；基础 2 组通过；公共组合通过 |
| Dialog | [dialog](../resources/views/components/dialog/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Direction | — | 不存在 |
| Drawer | [drawer](../resources/views/components/drawer/index.blade.php) | 已实现 Nova；基础 8 组通过；官方实际示例 8 组通过 |
| Dropdown Menu | [dropdown-menu](../resources/views/components/dropdown-menu/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 14 组通过 |
| Empty | [empty](../resources/views/components/empty/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 6 组通过 |
| Field | [field](../resources/views/components/field/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 10 组通过 |
| Hover Card | [hover-card](../resources/views/components/hover-card/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 2 组通过 |
| Input | [input](../resources/views/components/input.blade.php) | 已实现 Nova；基础 3 组通过；官方实际示例 10 组通过 |
| Input Group | [input-group](../resources/views/components/input-group/index.blade.php) | 已实现 Nova；基础 27 组通过；官方实际示例 7 组通过 |
| Input OTP | [input-otp](../resources/views/components/input-otp/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 8 组通过 |
| Item | [item](../resources/views/components/item/index.blade.php) | 已实现 Nova；基础 9 组通过；官方实际示例 24 组通过 |
| Kbd | [kbd](../resources/views/components/kbd/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 9 组通过 |
| Label | [label](../resources/views/components/label.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 4 组通过 |
| Marker | [marker](../resources/views/components/marker/index.blade.php) | 已实现 Nova；基础 3 组通过；官方实际示例 5 组通过 |
| Menubar | [menubar](../resources/views/components/menubar/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 12 组通过 |
| Message | [message](../resources/views/components/message/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 8 组通过 |
| Message Scroller | [message-scroller](../resources/views/components/message-scroller/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 1 组通过 |
| Native Select | [native-select](../resources/views/components/native-select/index.blade.php) | 已实现 Nova；基础 6 组通过；官方实际示例 6 组通过 |
| Navigation Menu | [navigation-menu](../resources/views/components/navigation-menu/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 1 组通过 |
| Pagination | [pagination](../resources/views/components/pagination/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 3 组通过 |
| Popover | [popover](../resources/views/components/popover/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Progress | [progress](../resources/views/components/progress/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 4 组通过 |
| Questionnaire | [questionnaire](../resources/views/components/questionnaire/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Radio Group | [radio-group](../resources/views/components/radio-group/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 6 组通过 |
| Resizable | [resizable](../resources/views/components/resizable/index.blade.php) | 已实现 Nova；基础 2 组通过；官方实际示例 5 组通过 |
| Scroll Area | [scroll-area](../resources/views/components/scroll-area/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 2 组通过 |
| Select | [select](../resources/views/components/select/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 15 组通过 |
| Separator | [separator](../resources/views/components/separator.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 4 组通过 |
| Sheet | [sheet](../resources/views/components/sheet/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 3 组通过 |
| Sidebar | [sidebar](../resources/views/components/sidebar/index.blade.php) | 已实现 Nova；基础 25 组通过；官方实际示例 4 组通过 |
| Skeleton | [skeleton](../resources/views/components/skeleton.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Slider | [slider](../resources/views/components/slider.blade.php) | 已实现 Nova；基础 6 组通过；官方实际示例 6 组通过 |
| Spinner | [spinner](../resources/views/components/spinner.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 5 组通过 |
| Switch | [switch](../resources/views/components/switch.blade.php) | 已实现 Nova；基础 8 组通过；官方实际示例 5 组通过 |
| Table | [table](../resources/views/components/table/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 7 组通过 |
| Tabs | [tabs](../resources/views/components/tabs/index.blade.php) | 已实现 Nova；基础 4 组通过；官方实际示例 13 组通过 |
| Textarea | [textarea](../resources/views/components/textarea.blade.php) | 已实现 Nova；基础 3 组通过；官方实际示例 5 组通过 |
| Toast | [toast](../resources/views/components/toast/index.blade.php)、[toaster](../resources/views/components/toaster.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 3 组通过 |
| Toggle | [toggle](../resources/views/components/toggle.blade.php) | 已实现 Nova；基础 18 组通过；官方实际示例 8 组通过 |
| Toggle Group | [toggle-group](../resources/views/components/toggle-group/index.blade.php) | 已实现 Nova；基础 24 组通过；官方实际示例 15 组通过 |
| Tooltip | [tooltip](../resources/views/components/tooltip/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 8 组通过 |
| Trace | [trace](../resources/views/components/trace/index.blade.php) | 已实现 Nova；基础 1 组通过；官方实际示例 8 组通过 |
| Typography | [heading](../resources/views/components/heading.blade.php)、[text](../resources/views/components/text.blade.php) | 部分对应；当前官网跳转至 Typeset，本库未提供官方 Typeset 排版系统 |

[Typography 当前跳转到 Typeset](https://ui.shadcn.com/docs/typeset)，不能仅凭 heading/text 两个入口就计为完整覆盖。rich-text 提供内容展示，但使用自有样式，也不能据此宣称已实现 Typeset。[Toaster 是官方 Toast 的配套组件](https://ui.shadcn.com/docs/components/base/toast)，本库将它单独作为根入口暴露。

## 本库扩展

这里的“扩展”指官方 Components 目录没有独立对应项，并不表示其他社区库没有该能力。UI 应遵循共同视觉规范；没有官方独立参考可用于声称逐项复刻。

| 本库组件 | 用途 |
| --- | --- |
| [cascader](../resources/views/components/cascader/index.blade.php) | 级联选择 |
| [color-picker](../resources/views/components/color-picker.blade.php) | 颜色选择 |
| [cropper](../resources/views/components/cropper/index.blade.php) | 图片裁剪 |
| [datetime-picker](../resources/views/components/datetime-picker/index.blade.php) | 本地日期时间选择 |
| [description-list](../resources/views/components/description-list/index.blade.php) | 属性/描述列表 |
| [editor](../resources/views/components/editor/index.blade.php) | 富文本编辑器 |
| [image](../resources/views/components/image.blade.php) | 图片展示 |
| [image-preview](../resources/views/components/image-preview/index.blade.php) | 图片预览 |
| [mentions](../resources/views/components/mentions/index.blade.php) | 提及输入 |
| [number-input](../resources/views/components/number-input/index.blade.php) | 数字输入 |
| [password-input](../resources/views/components/password-input.blade.php) | 密码输入与显隐 |
| [qr-code](../resources/views/components/qr-code.blade.php) | 二维码 |
| [rating](../resources/views/components/rating.blade.php) | 评分 |
| [rich-text](../resources/views/components/rich-text/index.blade.php) | 富文本内容展示（部分能力与 Typeset 重叠，未实现官方 Typeset） |
| [signature](../resources/views/components/signature.blade.php) | 签名 |
| [statistic](../resources/views/components/statistic/index.blade.php) | 统计数值 |
| [steps](../resources/views/components/steps/index.blade.php) | 步骤条 |
| [tags-input](../resources/views/components/tags-input/index.blade.php) | 标签输入 |
| [time-picker](../resources/views/components/time-picker.blade.php) | 时间选择 |
| [timeline](../resources/views/components/timeline/index.blade.php) | 时间线 |
| [tour](../resources/views/components/tour/index.blade.php) | 引导 |
| [transfer](../resources/views/components/transfer/index.blade.php) | 穿梭选择 |
| [tree](../resources/views/components/tree/index.blade.php) | 树 |
| [tree-select](../resources/views/components/tree-select/index.blade.php) | 树选择 |

## 基础封装

| 本库组件 | 归属 |
| --- | --- |
| [heading](../resources/views/components/heading.blade.php) | Typography 的标题部分 |
| [text](../resources/views/components/text.blade.php) | Typography 的段落/链接部分 |
| [toaster](../resources/views/components/toaster.blade.php) | Toast 的容器 |
| [icon](../resources/views/components/icon.blade.php) | Lucide 图标包装；官方组件也使用图标，不是本库独创能力 |

## 本轮实现与视觉证据

固定参考为 **shadcn Base UI / Nova** 提交 `e62c9435fff729a175f38983dee73b2cb4f6a069`。官方 TSX 由官方 `createStyleMap / transformStyleMap` 转换 Nova 类名，使用真实 Base UI 组件渲染；没有重画官方参考。字体为相同 Geist / Geist Mono，浏览器为同一 Playwright Chromium，视口为 1440×1000、390×1000，分别比较浅色、深色。

本轮建立 **298 组相同内容用例，覆盖 62 个直接对应根组件，1192 次基础/变体/展开态截图比较**；另有 **56 组控件的 hover、focus-visible、active，672 次状态比较**。基础矩阵差异 0 项，交互矩阵差异 0 项；运行时错误 0 项。23 个扩展组件另有 92 张浅色/深色、桌面/移动截图，页面溢出 0 项。

截图比较固定为灰度字体栅格：Windows 下原生 top-layer 与 portal 的 LCD 子像素字体处理不同，所以两边同时使用 `--disable-lcd-text`。比较使用 pixelmatch 默认抗锯齿边缘排除及阈值 0.1；“零差异”表示此口径下没有差异，**不是 PNG 文件逐字节相同**。验收集合由 cases.json、catalog-cases.json、acceptance-suites.json 明确列出。静态截图关闭动画；官方实际示例、公开组合和动画采样另有动态报告与完整机器结果，见 dynamic-report.html 和 acceptance-verification.json。

| 重点修改 | 当前实现 |
| --- | --- |
| Input Group | 四种 addon 布局、四种内嵌按钮尺寸、全部按钮变体；输入、文本域、按钮直接复用现有组件 |
| DatePicker | 使用 Calendar 的原生月份、年份标题下拉。浏览月份时保留选择值和弹层状态 |
| 基础样式 | 官方 Nova token、Geist 字体、尺寸、圆角、边框、阴影、焦点与错误状态；清除旧样式冲突 |
| 弹层与菜单 | 官方间距、宽度、居中/边缘位置、方向、背景、关闭控件与动作区；选择项复用 Checkbox / Radio |
| ScrollArea | 内容保留原生 viewport，统一可拖动滚动条，支持横纵方向与键盘 |
| 扩展组件 | PasswordInput / NumberInput / Combobox 组合 InputGroup；Cascader / TreeSelect 组合 Popover；Transfer 组合 Card / ScrollArea / Button；编辑器等采用共同 token |

该批历史截图和机器结果保存在维护者的本地演示环境。现行生产包浏览器验收见 [消费端测试](../tests/distribution/consumer.test.mjs)。

## 覆盖边界

组件目录的“存在”与视觉矩阵通过分别记录。Typography 仍仅部分覆盖，Aspect Ratio、Chart、Direction 仍不存在，本次没有将清单缺失项虚报为已实现。扩展组件没有官方独立参考，因此记录共享组件和视觉规范，不称为独立官方组件的逐项复刻。

完整验收包含固定官方实际示例及列明的公开尺寸、状态、组合和动画采样，动态结果见后文。其他浏览器、操作系统和任意业务内容不属于本次固定条件下的截图证明范围。

<!-- NOVA_ACCEPTANCE_START -->
## 动画与矩阵外组合验收

2026-11-18 完成固定官方版本的 459 个实际示例和列明的公开组合、连续交互、动画采样。基础/尺寸 1192 次、控件状态 672 次、官方实际示例及组合/动画 9628 次，共 **11492 次对照通过**；扩展组件另有 92 张浅/深色与两种宽度截图。未通过项和待验收项均为 0。

| 验收内容 | 结果 |
| --- | --- |
| 进入/退出、中途反向、连续切换 | 浅/深色、桌面/移动端通过；弹层开合及连续切换另有减少动态效果偏好验证 |
| 循环动画与折叠 | Spinner、Skeleton、OTP、Marker 以及 Accordion、Collapsible、Trace 普通/减少动态效果偏好通过 |
| Drawer | 四方向、嵌套、吸附点、内容滚动边界、鼠标/触摸、回弹/关闭通过 |
| Navigation Menu / Sidebar | 共用视口尺寸过渡、顶部与内容焦点、顶部普通链接、图标折叠、移动面板通过 |
| Toast | 进入/退出、堆叠、悬停/焦点展开、拖动、F6/Tab/Escape、计时暂停恢复、Livewire 重排与异步操作通过 |
| Carousel | 与参考同版本 Embla 引擎；逐项吸附、拖动惯性、逐时刻位移、容器重测和减少动画偏好通过 |
| 公共调用 | 46 项浏览器回归；184 项 PHP 测试、381 个断言；PHPStan、Pint、JS 架构、Prettier、包/应用构建通过 |

<!-- NOVA_ACCEPTANCE_END -->
