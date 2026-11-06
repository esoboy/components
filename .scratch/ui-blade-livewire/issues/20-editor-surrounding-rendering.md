# 20: 收回 Editor 周边内容渲染

**What to build:** Editor 候选菜单、溢出分组结构、表单提示和状态文字由组件数据及 Blade 输出。

**Blocked by:** 02：统一浮层和 Sidebar 的自定义触发接口

**Status:** resolved

**Batch:** 3

**Audit coverage:** 原问题 17。

- [x] 浏览器保留必要的编辑、测量、定位与 SDK 生命周期。
- [x] 不以第三方编辑引擎为由保留全部周边 UI 的前端渲染。
- [x] 验证候选搜索与插入、工具栏宽窄切换、提示、上传和正文绑定。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

PHP、Vitest 和 Chromium 覆盖候选过滤、斜杠与 Emoji 插入、宽窄工具栏、提示、上传与绑定；保留 Tiptap 和 KaTeX 文档内能力。
