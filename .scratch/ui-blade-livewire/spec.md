# UI 库 Blade 优先改造

## 目标与规则

- Blade 优先，Livewire 4 辅助，Alpine 完成必要浏览器交互。
- 能由 Blade + Livewire 4 完成的内容和数据操作不交给 Alpine。
- 组件接收变量，由 Blade 控制 HTML 渲染；不能以 Alpine provider 包装前端渲染作为合规依据。
- Alpine 负责必要的开关、焦点、定位、拖动、光标和浏览器引擎生命周期，自有交互不使用独立原生 JS 运行时。
- trigger、close 等行为子组件仅处理行为，具体 UI 由调用方 slot 提供。
- 内部 Livewire 的扩展插槽必须在更新后继续工作；父级业务操作必须作用于正确组件。
- 保留样式、键盘操作、字段校验、表单及多实例能力，不引入不必要的运行时或状态子系统。
- 第三方依赖不自动豁免；逐项区分必要的浏览器能力与可收回的周边内容渲染。

## 已批准批次

1. T01–T06：行为接口和插槽能力。
2. T07–T13：普通控件、筛选结果与上传列表。
3. T14–T22：反馈、实时展示、复杂组件与总验收。

每张工单的 Batch 表示交付批次，Blocked by 仅列技术前置依赖。先完成当前批次，再进入下一批。

## 验证

每张工单交付实现、演示调用、相关验证和说明更新。优先公共 Blade 调用、公共 Livewire 状态/action 与真实浏览器交互；架构边界另作源码约束检查。运行类型检查、目标测试及最终全量测试，最后作 Standards/Spec 审查。

## 审查基线

本次开工前 HEAD：dac735ea4d54c5a756fbbb03c1347fb4f35310f7。既有无关 IDE 修改不属于本次提交。

## 原审计覆盖

- T01：Dialog 家族支持纯行为 trigger/close；覆盖原问题 1。
- T02：统一浮层和 Sidebar 的自定义触发接口；覆盖原问题 2、3。
- T03：恢复 Combobox 的组合插槽能力；覆盖原问题 4。
- T04：恢复 Calendar、Date Picker、Cascader 的扩展插槽；覆盖原问题 4。
- T05：恢复 Tree、Tree Select、Transfer 的扩展插槽；覆盖原问题 4。
- T06：恢复 Tags Input、Emoji Picker、Questionnaire 的扩展插槽；覆盖原问题 4。
- T07：Select 统一由 Blade 渲染选中内容；覆盖原问题 10。
- T08：Number Input、Rating 的普通值操作迁入 Livewire；覆盖原问题 11。
- T09：OTP 字符与字数统计由 Blade 持续渲染；覆盖原问题 12、13。
- T10：Command 搜索结果由 Livewire 筛选、Blade 渲染；覆盖原问题 8。
- T11：Mentions 统一服务端候选筛选规则；覆盖原问题 9。
- T12：上传中的文件列表交给组件渲染；覆盖原问题 7。
- T13：Data Table 正确响应服务端展开状态；覆盖原问题 18。
- T14：Toast 内容和列表由内部 Livewire 渲染；覆盖原问题 5。
- T15：Tour 步骤与操作区交给 Livewire 和 Blade；覆盖原问题 6。
- T16：Confirm 使用组件数据渲染确认内容；覆盖原问题 17。
- T17：Countdown、验证码按钮统一时间状态与文字渲染；覆盖原问题 14。
- T18：Color Picker 分离即时预览与正式值渲染；覆盖原问题 15。
- T19：Image Preview 支持服务端内容与自定义操作区；覆盖原问题 16。
- T20：收回 Editor 周边内容渲染；覆盖原问题 17。
- T21：Code Block 高亮由服务端准备、Blade 输出；覆盖原问题 17。
- T22：架构约束落地与完整回归；覆盖原问题 19。
