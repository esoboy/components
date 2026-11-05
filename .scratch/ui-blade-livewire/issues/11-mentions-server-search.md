# 11: Mentions 统一服务端候选筛选规则

**What to build:** Mentions 使用组件提供的候选项，服务端结果不被浏览器再次筛除。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 2

**Audit coverage:** 原问题 9。

- [ ] 验证查询、远程结果替换、多前缀、空状态和禁用项。
- [ ] Alpine 只负责光标识别、定位、导航与插入等必要浏览器行为。
- [ ] 保留输入绑定和搜索、选择公共事件。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
