# 21: Code Block 高亮由服务端准备、Blade 输出

**What to build:** 代码及高亮结构在服务端准备，Blade 输出高亮结果，复制行为由 Alpine 提供。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 3

**Audit coverage:** 原问题 17。

- [ ] 浏览器不再动态创建高亮 token 节点。
- [ ] 验证转义、支持语言、未知语言、长代码及 Livewire 内容更新。
- [ ] 不增加调用方初始化要求，保留样式与复制反馈。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
