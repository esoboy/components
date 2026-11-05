# UI 库三批实施工单

规格见 [spec.md](spec.md)。所有工单已经用户批准发布；实施时先领取，再记录验收证据和状态。

| 工单 | 批次 | 标题 | 技术依赖 |
| --- | --- | --- | --- |
| [01](issues/01-dialog-behavior-slots.md) | 1 | Dialog 家族支持纯行为 trigger/close | None |
| [02](issues/02-overlay-sidebar-slots.md) | 1 | 统一浮层和 Sidebar 的自定义触发接口 | 01 |
| [03](issues/03-combobox-slots.md) | 1 | 恢复 Combobox 的组合插槽能力 | None |
| [04](issues/04-date-cascader-slots.md) | 1 | 恢复 Calendar、Date Picker、Cascader 的扩展插槽 | 03 |
| [05](issues/05-tree-transfer-slots.md) | 1 | 恢复 Tree、Tree Select、Transfer 的扩展插槽 | 03 |
| [06](issues/06-tags-emoji-questionnaire-slots.md) | 1 | 恢复 Tags Input、Emoji Picker、Questionnaire 的扩展插槽 | 03 |
| [07](issues/07-select-server-rendering.md) | 2 | Select 统一由 Blade 渲染选中内容 | None |
| [08](issues/08-number-rating-actions.md) | 2 | Number Input、Rating 的普通值操作迁入 Livewire | None |
| [09](issues/09-otp-count-rendering.md) | 2 | OTP 字符与字数统计由 Blade 持续渲染 | None |
| [10](issues/10-command-server-search.md) | 2 | Command 搜索结果由 Livewire 筛选、Blade 渲染 | None |
| [11](issues/11-mentions-server-search.md) | 2 | Mentions 统一服务端候选筛选规则 | None |
| [12](issues/12-upload-pending-rendering.md) | 2 | 上传中的文件列表交给组件渲染 | None |
| [13](issues/13-data-table-controlled-expansion.md) | 2 | Data Table 正确响应服务端展开状态 | None |
| [14](issues/14-toast-server-rendering.md) | 3 | Toast 内容和列表由内部 Livewire 渲染 | 01 |
| [15](issues/15-tour-server-steps.md) | 3 | Tour 步骤与操作区交给 Livewire 和 Blade | 01 |
| [16](issues/16-confirm-server-content.md) | 3 | Confirm 使用组件数据渲染确认内容 | 01, 02 |
| [17](issues/17-countdown-server-state.md) | 3 | Countdown、验证码按钮统一时间状态与文字渲染 | None |
| [18](issues/18-color-picker-state.md) | 3 | Color Picker 分离即时预览与正式值渲染 | 02 |
| [19](issues/19-image-preview-content.md) | 3 | Image Preview 支持服务端内容与自定义操作区 | 01 |
| [20](issues/20-editor-surrounding-rendering.md) | 3 | 收回 Editor 周边内容渲染 | 02 |
| [21](issues/21-code-block-server-highlighting.md) | 3 | Code Block 高亮由服务端准备、Blade 输出 | None |
| [22](issues/22-architecture-regression.md) | 3 | 架构约束落地与完整回归 | 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21 |
