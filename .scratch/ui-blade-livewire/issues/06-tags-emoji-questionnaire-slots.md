# 06: 恢复 Tags Input、Emoji Picker、Questionnaire 的扩展插槽

**What to build:** 标签、Emoji 附加区域及问卷内容和操作区可自定义，保留默认调用。

**Blocked by:** 03：恢复 Combobox 的组合插槽能力

**Status:** resolved

**Batch:** 1

**Audit coverage:** 原问题 4。

- [x] 增删标签、搜索 Emoji 和切换步骤后 slot 继续有效。
- [x] 父级业务操作正确，内部校验与完成事件保留。
- [x] 不把动态内容以静态快照传给内部 Livewire。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

## 执行记录

标签、Emoji 和问卷支持区域插槽及相应自定义入口。批次相关 PHP 70 项与 Chromium 33 项通过，包含标签增删、Emoji 搜索与问卷步骤。
