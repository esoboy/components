# 08: Number Input、Rating 的普通值操作迁入 Livewire

**What to build:** 加减、限值、评分选择与清空由组件处理，Blade 输出正式值、选中和禁用状态。

**Blocked by:** None (can start immediately)

**Status:** resolved

**Batch:** 2

**Audit coverage:** 原问题 11。

- [x] 验证小数步长、边界、空值、评分清空及服务端重置。
- [x] 必要的长按和焦点交互可保留在 Alpine，不保留两套正式值规则。
- [x] 保留绑定修饰符、传统表单值、字段错误和无障碍操作。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

Number Input、Rating 的 PHP / Chromium 回归覆盖边界、步进、清空、键盘和交互。
