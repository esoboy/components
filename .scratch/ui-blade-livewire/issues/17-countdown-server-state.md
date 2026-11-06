# 17: Countdown、验证码按钮统一时间状态与文字渲染

**What to build:** 截止时间与可重发状态由组件持有，Blade 输出时间和按钮内容，保留调用方 slot。

**Blocked by:** None (can start immediately)

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 14。

- [x] 不以覆盖 slot 文字实现倒计时或重新发送。
- [x] 验证重启、结束事件、导航恢复、重复点击及多实例。
- [x] 验收更新频率与请求成本，不引入无界轮询。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP、Vitest 和 Chromium 覆盖单次结束、重启、命名截止时间恢复、请求调度与验证码按钮禁用/重发。
