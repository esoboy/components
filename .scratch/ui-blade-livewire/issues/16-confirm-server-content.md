# 16: Confirm 使用组件数据渲染确认内容

**What to build:** 确认标题、正文、按钮内容和危险样式通过组件变量与 Blade 输出。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close；02：统一浮层和 Sidebar 的自定义触发接口

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 17。

- [x] 移除直接替换文字，保留对话框和 Popover 两种形式。
- [x] 确认执行一次、取消不执行，连续请求和焦点恢复正确。
- [x] 自有运行时交互由 Alpine 管理，不新增全局原生事件系统。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP 和 Chromium 覆盖默认与危险样式、自定义标题/描述/按钮、Dialog/Popover、取消与确认一次；Vitest 验证准备完成前不会打开。
