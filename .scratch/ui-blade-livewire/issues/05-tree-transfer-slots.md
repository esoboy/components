# 05: 恢复 Tree、Tree Select、Transfer 的扩展插槽

**What to build:** 树、树选择和穿梭框允许自定义节点、选项及操作区，仍由组件执行搜索、展开、选择和移动。

**Blocked by:** 03：恢复 Combobox 的组合插槽能力

**Status:** ready-for-agent

**Batch:** 1

**Audit coverage:** 原问题 4。

- [ ] 搜索、移动、懒加载后自定义内容正确更新。
- [ ] 验证禁用节点、父子选择规则和多实例隔离。
- [ ] 默认数据用法不要求调用方编写内部 Alpine 初始化。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
