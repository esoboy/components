# Changelog

## v2.0.0 - 2026-11-23

- `media-preview` 取代 `image-preview`，支持图片、音频和视频。升级时将旧组件改为 `<x-ui::media-preview type="image">`，保留 `src` 和 `alt`，将 `<x-slot:trigger>` 中的内容移入默认插槽。[媒体预览示例](docs/components.md#media-preview)
- 新增 `icon-select`，可按名称搜索 Lucide 图标，使用分页按钮或键盘浏览，并通过 `wire:model` 或原生表单取得选中的图标名称。
- 按钮、徽标、选择控件、签名和二维码等组件支持通过 `color` 单独设色。可传入运行时的 Tailwind 色名、宿主自定义主题色或 CSS 颜色值。[组件配色说明](README.md#设置单个组件的颜色)

## v1.0.2 - 2026-11-20

- 恢复随 Laravel Boost 安装的 `esoboy-ui-development` 技能。Composer 包包含当前组件 API 的说明，以及从公共渲染测试用例同步的 90 个 Blade 组件示例。

## v1.0.1 - 2026-11-20

- 修复 Livewire 重绘后 Dialog 触发按钮失效和页面滚动锁残留。替换后的按钮可以打开弹层并接回焦点。
- 非受控 Slider 在同一 `wire:key` 下保留已编辑的手柄、提交值和重置初值。更换 key 时采用新的初值和手柄数量。
- Editor、Cropper、Signature、Rating 和选择控件支持通过 `form` 关联外部表单。只读问卷切换题目时，FormData 始终包含各题已填写的答案。
- Pagination 和 DataTable 默认页脚根据宿主的分页动作选择导航方式。普通 Livewire 宿主可以使用分页链接及手动跳页。
- 修复窄屏 Dropdown Menu 及子菜单的横向溢出。同步此前的组件样式隔离、Combobox 搜索与定位、Calendar 导航和示例覆盖更新，并在独立 Laravel 宿主中验证生产 ZIP。

## v1.0.0 - 2026-11-19

- Composer 生产包提供一份可导入的 Tailwind CSS 源文件，组件样式位于 `components` 层。宿主布局通过 `@uiScript` 加载通用 JS，编辑器按需加载独立脚本。安装和升级见 [README](README.md)。
- 图标名称严格使用锁定版本 Lucide 的正式名称。使用 `circle-help` 的调用方需改用 `circle-question-mark`。

- 安装和升级组件库时无需发布静态资源。Editor 按需从包内加载，静态托管可手动发布 `ui-assets`。

- Editor 改用 Tiptap UI 工具栏与表格上下文菜单。图片、音频、视频和文件支持远程地址及应用多选，图片与视频支持拖动缩放、设置与更换。

- Cropper / Signature 删除内部 Livewire，直接复用调用方值；Signature 完整 canvas 绘制，支持连续笔画、回填、取消、重置及多实例。
- Tree 系列清理 options，Tree 清理 placeholder；Cascader 清理 expanded，补齐初始定位、父级回填及节点更换；Transfer 固定多选并清理 multiple / placeholder。
- Mentions 普通输入延迟同步，仅有效且改变的 @query 请求候选；保护慢响应与过期候选选择。Select 和分页大小选择器保持 Base UI 体验。

- NumberInput、Slider、InputOtp、Editor 移除内部 Livewire 实例，直接绑定调用方；保留互斥值协议，补齐原生表单、重置、父级回填、多实例与提交修饰符验证。受控事件使用 x-on:value-changed 和显式 $wire。

- Editor: basic/full 工具栏；移除斜杠和表情交互、不可见字符、空行插入、YouTube/Twitch。
- 删除 EmojiPicker；增加 Base UI Questionnaire（单/多选、自由输入、跳过、校验、快捷键、受控导航、重置）。
- 修复 Switch 滑块错位与 Cascader 尾列边框、Livewire 更新后的定位丢失。

- The library provides 90 public components. Selection, menus, layouts, native controls and composition follow the pinned shadcn Base UI / Nova examples.
- Remove verification-code-button, link, float-button, back-to-top, aspect-ratio, confirm, anchor, countdown, watermark and result. Use the Button / Text link property and AlertDialog composition.
- Ship the Tiptap v3 editor as one complete Web Component asset, with independent input/output formats, isolated instances and a shared HTML/Markdown content contract with Blade-rendered RichText.
- Preserve supported content through Markdown with embedded HTML and fix preview positioning, Livewire ownership, focus, scrolling and rendering boundaries.


## v0.7.1 - 2026-11-14

- Fix active Tabs panels disappearing after Livewire updates, including the Date Picker's range time panels. Preserve current native tab values while bound values and Blade content continue to update.

## v0.7.0 - 2026-11-13

- Preserve caller-owned actions and nested Livewire component state in custom Select and Command content.
- Forward behavior-slot attributes without double escaping and omit disabled controls from native form values.
- Compute Tree selection and search states once per render; prevent Combobox from confirming stale search results.
- Add CSS group entries with their own dependencies and Tailwind sources, sharing the existing Nova theme. See [README.md](README.md) for selective imports.

Known issue: the range Date Picker's Hour panels can become hidden after a Livewire update. This also reproduces in v0.6.0 and remains unresolved in this release.

## v0.6.0 - 2026-11-12

- Use the same month picker for single and range selection. Remove quarter granularity from Date Picker. See [v0.6.0 migration notes](https://github.com/esoboy/components/blob/v0.6.0/package/UPGRADING.md) to migrate quarter values to month ranges.
- Keep Combobox closed after dismissal and Livewire updates. Forward the accessible label to the Combobox control.
- Clear inline styles in the Editor selection and preserve the unstyled result when reopening it.
- Show Input OTP changes sooner and adjust Input Group addon spacing.

## v0.5.0 - 2026-11-09

- Add month and quarter selection to Date Picker, including range and multiple selection, presets and bounds.
- Improve control labelling, Select positioning when reopening and Slider feedback between steps. Align Button and Toggle sizing.
- Pass dropped and pasted files and image imports to the application through `ui-editor-media-request`. See [v0.5.0 migration notes](https://github.com/esoboy/components/blob/v0.5.0/package/UPGRADING.md) for migration from the package uploader.
- Wrap Toaster in Livewire's `@persist` in the application layout to keep notifications across `wire:navigate`.

## v0.4.0 - 2026-11-08

- Add Icon `size="xs|sm|default|lg"` presets (12, 14, 16 and 20px). Omitted sizes follow the containing control's icon sizing, or use 16px standalone. Explicit sizes override automatic control sizing.
- Add Heading `variant="title|subtitle"` and Text `variant="primary|secondary"`. Variants use the theme's foreground or muted foreground without changing size or weight. Explicit named `color` values take priority.
- Update the public examples, component reference and Laravel Boost skill for the new options.

Heading and Text now use their default variant's foreground color instead of inheriting the surrounding text color. Set `variant="subtitle"` or `variant="secondary"` for muted text, or pass an explicit named `color`.
