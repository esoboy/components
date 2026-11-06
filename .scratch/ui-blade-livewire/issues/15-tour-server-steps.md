# 15: Tour 步骤与操作区交给 Livewire 和 Blade

**What to build:** 当前步骤、内容、计数和操作区由组件渲染，支持自定义操作控件。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 6。

- [x] 切换步骤不再克隆模板或替换主体内容。
- [x] Alpine 只负责定位、遮罩、滚动、焦点和开关。
- [x] 验证开始、跳步、前后切换、完成、提前关闭及事件。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP、Vitest 与 Chromium 覆盖开始、定位、前后切换、指定步骤、完成与提前关闭。演示使用自定义操作 slot，并验证步骤内容中的父级操作在 Livewire 更新后仍可用。
