# 09: OTP 字符与字数统计由 Blade 持续渲染

**What to build:** OTP 初始字符和后续值、字数及超限提示均由组件数据与 Blade 输出。

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

**Batch:** 2

**Audit coverage:** 原问题 12、13。

- [ ] 已有初始值时无需前端复制字符即可显示，修改与服务端重置均正确。
- [ ] 字数及超限状态随值更新，不依赖覆盖 textContent 或忽略服务端渲染。
- [ ] Alpine 只保留活动格、光标和焦点行为。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
