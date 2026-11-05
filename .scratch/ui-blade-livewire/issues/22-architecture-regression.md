# 22: 架构约束落地与完整回归

**What to build:** 文档、公共接口和架构检查符合本次规则，完成全部组件的最终一致性验收。

**Blocked by:** 01：Dialog 家族支持纯行为 trigger/close；02：统一浮层和 Sidebar 的自定义触发接口；03：恢复 Combobox 的组合插槽能力；04：恢复 Calendar、Date Picker、Cascader 的扩展插槽；05：恢复 Tree、Tree Select、Transfer 的扩展插槽；06：恢复 Tags Input、Emoji Picker、Questionnaire 的扩展插槽；07：Select 统一由 Blade 渲染选中内容；08：Number Input、Rating 的普通值操作迁入 Livewire；09：OTP 字符与字数统计由 Blade 持续渲染；10：Command 搜索结果由 Livewire 筛选、Blade 渲染；11：Mentions 统一服务端候选筛选规则；12：上传中的文件列表交给组件渲染；13：Data Table 正确响应服务端展开状态；14：Toast 内容和列表由内部 Livewire 渲染；15：Tour 步骤与操作区交给 Livewire 和 Blade；16：Confirm 使用组件数据渲染确认内容；17：Countdown、验证码按钮统一时间状态与文字渲染；18：Color Picker 分离即时预览与正式值渲染；19：Image Preview 支持服务端内容与自定义操作区；20：收回 Editor 周边内容渲染；21：Code Block 高亮由服务端准备、Blade 输出

**Status:** ready-for-agent

**Batch:** 3

**Audit coverage:** 原问题 19。

- [ ] 各组件测试随工单完成，本工单检查整合后的全量测试、类型检查、构建和桌面移动端演示。
- [ ] 检查行为插槽、动态插槽、渲染归属、父级事件和多实例。
- [ ] 清理已迁移的模板克隆和废弃适配代码，保留必要浏览器能力并说明边界。
- [ ] 执行 Standards 与 Spec 两轴代码审查并修复有效发现，提交到当前分支。
- [ ] 更新公共调用示例和说明，通过相关测试并记录验证结果。
