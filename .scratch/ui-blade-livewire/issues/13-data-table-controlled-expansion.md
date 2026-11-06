# 13: Data Table 正确响应服务端展开状态

**What to build:** 表格同一行经过 Livewire 更新后，服务端 expanded 变化能正确生效，不被历史本地集合覆盖。

**Blocked by:** None (can start immediately)

**Status:** resolved

**Batch:** 2

**Audit coverage:** 原问题 18。

- [x] 明确受控与非受控展开行为，保留纯浏览器展开的合理交互。
- [x] 验证详情行、子行、翻页及同 ID 数据替换。
- [x] 选择、固定列和滚动交互不回退。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

Data Table 的 PHP、Vitest 和 Chromium 回归覆盖展开控制、同 ID 更新、分页与既有表格交互。
