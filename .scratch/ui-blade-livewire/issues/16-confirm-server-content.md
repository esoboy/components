# 16: Confirm 使用组件数据渲染确认内容

**What to build:** 确认标题、正文、按钮内容和危险样式通过组件变量与 Blade 输出。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close；02：统一浮层和 Sidebar 的自定义触发接口

**Status:** ready-for-agent

**Batch:** 3

**Audit coverage:** 原问题 17。

- [ ] 移除直接替换文字，保留对话框和 Popover 两种形式。
- [ ] 确认执行一次、取消不执行，连续请求和焦点恢复正确。
- [ ] 自有运行时交互由 Alpine 管理，不新增全局原生事件系统。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
