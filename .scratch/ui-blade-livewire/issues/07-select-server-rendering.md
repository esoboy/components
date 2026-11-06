# 07: Select 统一由 Blade 渲染选中内容

**What to build:** 初始值、用户选择和服务端修改都通过统一的组件状态与 Blade 更新选中标签及结果。

**Blocked by:** None (can start immediately)

**Status:** resolved

**Batch:** 2

**Audit coverage:** 原问题 10。

- [x] 不再从选项复制 innerHTML 到选中显示区域。
- [x] 普通和富文本标签、空值、禁用项及表单值正确。
- [x] Alpine 仅保留浮层定位、焦点和键盘导航。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

Select、Data Table 的 PHP / Chromium 回归覆盖普通标签、富标签、禁用选项、表单值及 Livewire 更新。
