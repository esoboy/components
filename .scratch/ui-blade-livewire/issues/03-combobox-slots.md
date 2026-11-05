# 03: 恢复 Combobox 的组合插槽能力

**What to build:** Combobox 的默认 options 调用与自定义选项、选中内容、空状态均可使用，搜索选择继续由内部 Livewire 处理。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 1

**Audit coverage:** 原问题 4。

- [ ] 自定义区域初始可见且随内部 Livewire 更新重新渲染，不能固化为静态 HTML。
- [ ] 插槽中的父级业务 action 作用于正确组件，多个实例互不干扰。
- [ ] 验证单选、多选、清空、服务端替换 options/value 及验证错误。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
