# 02: 统一浮层和 Sidebar 的自定义触发接口

**What to build:** Popover、Dropdown Menu、Tooltip、Hover Card 和 Sidebar 可使用调用方提供的任意适用触发控件。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close

**Status:** resolved

**Batch:** 1

**Audit coverage:** 原问题 2、3。

- [x] 触发子组件不再选择或生成具体 UI，不限制为库内 as 组件。
- [x] Sidebar 输出调用方 slot，悬停、点击、焦点、禁用及重复组件行为保持正确。
- [x] 演示采用新调用方式，原生 DOM 事件运行时不得引入。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

## 执行记录

五类触发器绑定到调用方实际控件。可在一个按钮上组合 Tooltip 和 Dropdown Menu。Blade、编辑器兼容性等相关 PHP 测试 105 项通过，Vitest 63 项通过，Chromium 17 项通过，包构建通过。
