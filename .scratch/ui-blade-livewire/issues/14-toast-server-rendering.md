# 14: Toast 内容和列表由内部 Livewire 渲染

**What to build:** 通过组件状态新增、更新、关闭通知，由 Blade 输出标题、描述、图标和操作区。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 5。

- [x] 不再克隆模板、填内容或由前端创建通知列表。
- [x] 关闭和操作入口采用行为插槽，计时、暂停、滑动、堆叠测量保留。
- [x] 验证同 ID 更新、操作回调、多通知及导航后行为。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP、Vitest、Chromium 覆盖同 ID 更新、超时/滑动/暂停、操作回调、关闭和导航保留，演示调用自定义操作 slot。
