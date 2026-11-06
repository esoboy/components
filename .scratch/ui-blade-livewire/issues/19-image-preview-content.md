# 19: Image Preview 支持服务端内容与自定义操作区

**What to build:** 组件渲染当前图片、替代文字、页码和前后状态，调用方可提供关闭、翻页等控件。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 16。

- [x] Alpine 仅保留开关、缩放、旋转、平移等必要交互。
- [x] 验证图片集合更新、边界切换、加载状态、键盘和多实例。
- [x] 操作子组件不强制 UI，内容不由前端复制或拼接。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP、Vitest 和 Chromium 覆盖服务端图片与页码、前后边界、单图、多实例、加载、键盘、缩放和自定义关闭控件。
