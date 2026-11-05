# 04: 恢复 Calendar、Date Picker、Cascader 的扩展插槽

**What to build:** 日期、日历及级联组件保留默认数据调用，并支持符合自身结构的自定义内容和操作区域。

**Blocked by:** 03：恢复 Combobox 的组合插槽能力

**Status:** ready-for-agent

**Batch:** 1

**Audit coverage:** 原问题 4。

- [ ] 使用已验证的内部 Livewire 插槽方式，保持自定义区域随月份、值、路径更新。
- [ ] 验证日期限制、时间、范围和禁用路径，默认样式不变。
- [ ] 父级操作和多实例绑定正确。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
