# 07: Select 统一由 Blade 渲染选中内容

**What to build:** 初始值、用户选择和服务端修改都通过统一的组件状态与 Blade 更新选中标签及结果。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 2

**Audit coverage:** 原问题 10。

- [ ] 不再从选项复制 innerHTML 到选中显示区域。
- [ ] 普通和富文本标签、空值、禁用项及表单值正确。
- [ ] Alpine 仅保留浮层定位、焦点和键盘导航。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
