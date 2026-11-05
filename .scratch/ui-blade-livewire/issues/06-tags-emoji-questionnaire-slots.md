# 06: 恢复 Tags Input、Emoji Picker、Questionnaire 的扩展插槽

**What to build:** 标签、Emoji 附加区域及问卷内容和操作区可自定义，保留默认调用。

**Blocked by:** 03：恢复 Combobox 的组合插槽能力

**Status:** ready-for-agent

**Batch:** 1

**Audit coverage:** 原问题 4。

- [ ] 增删标签、搜索 Emoji 和切换步骤后 slot 继续有效。
- [ ] 父级业务操作正确，内部校验与完成事件保留。
- [ ] 不把动态内容以静态快照传给内部 Livewire。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
