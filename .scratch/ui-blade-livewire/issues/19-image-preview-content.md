# 19: Image Preview 支持服务端内容与自定义操作区

**What to build:** 组件渲染当前图片、替代文字、页码和前后状态，调用方可提供关闭、翻页等控件。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close

**Status:** ready-for-agent

**Batch:** 3

**Audit coverage:** 原问题 16。

- [ ] Alpine 仅保留开关、缩放、旋转、平移等必要交互。
- [ ] 验证图片集合更新、边界切换、加载状态、键盘和多实例。
- [ ] 操作子组件不强制 UI，内容不由前端复制或拼接。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
