# 01: Dialog 家族支持纯行为 trigger/close

**What to build:** Dialog、Sheet、Drawer、Alert Dialog 的触发、关闭、确认和取消子组件仅提供行为，调用方以 slot 提供完整 UI。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 1

**Audit coverage:** 原问题 1。

- [ ] 完整按钮放入 trigger/close/action/cancel 后不产生嵌套按钮，属性及无障碍语义落在实际控件上。
- [ ] 保留服务端开关、表单提交、键盘关闭、禁用状态与多实例隔离。
- [ ] 先以 Dialog 完成最小行为插槽方式，再迁移同类组件；不引入额外运行时系统。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
