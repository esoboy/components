## Agent skills

### Issue tracker

使用本地 Markdown 跟踪任务，每张工单独立保存。
具体规则见 docs/agents/issue-tracker.md。

### Domain docs

采用 single-context 布局：根目录 GLOSSARY.md 与 docs/adr/。
读取规则见 docs/agents/domain.md。

## UI 库组件规则

新增、修改和审查 UI 库组件及其调用示例时，按以下规则执行。

- **Blade 优先渲染。** 组件通过 props 和 slot 接收数据与内容，由 Blade 输出初始和后续的 HTML，包括文本、选项、列表和状态提示。禁止使用 Alpine 生成或替换这些内容。
- **Livewire 4 管理数据。** 需要更新数据时，由 Livewire 管理组件值、筛选结果、校验和服务端操作，更新后的内容继续由 Blade 渲染。Blade 与 Livewire 能完成的工作，禁止再引入 Alpine。
- **Alpine 只做必要的浏览器交互。** 弹层开关、焦点、键盘导航、定位、滚动和拖动等交互由组件库内部处理。项目的自有前端交互统一使用 Alpine，禁止另写独立原生 JavaScript 交互逻辑。第三方浏览器引擎的适配也遵守同一渲染边界。
- **行为子组件提供 slot。** 弹层的 `trigger`、`close` 等子组件只绑定触发或关闭行为并提供 slot。调用方在 slot 中放入按钮等 UI，决定其内容和样式。行为子组件禁止生成默认控件，调用方无需绑定内部事件。
- **组件禁止提供 `as` 属性。** 组件按自身语义输出标签。需要自定义触发控件时，由调用方通过 slot 提供。
- **图标只使用正式名称。** 图标组件和调用示例直接使用图标库的正式名称，禁止添加别名或名称映射。
- **组件只维护当前 API。** 接口变更时直接迁移受影响的调用和示例。禁止为旧属性、旧名称或旧调用方式保留兼容分支、回退逻辑或适配层。
- **验证公共调用。** 有内部 Livewire 组件时，确认父组件更新后调用方的 slot 仍能更新，slot 中的业务操作仍由调用方所属的 Livewire 组件执行。
