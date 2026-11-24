# 性能与验收

全量 CSS 对照测于 2026-11-24，服务端历史测量日期为 2026-11-18。生产脚本与编辑器样式的大小由每次构建输出。

## 资源交付与预算

包构建检查通用行为 gzip ≤24 KiB、完整编辑器 gzip ≤600 KiB，并拒绝动态 chunk 和外部运行时 import。常规入口包含与 Nova 官方相同的 Embla 8.5.2 引擎及 Toast 堆叠/手势行为，因此本轮将完整单入口预算从 16 KiB 调整为 24 KiB；这是为交互一致性增加的传输成本。仍不增加动态 chunk、外部 import 或独立轮播请求。交付方式如下。

| 资源 | 交付方式 |
| --- | --- |
| ui.css | 普通组件与只读 RichText 的 Tailwind 源样式，由应用导入并参与应用的 CSS 构建 |
| ui.js | `@uiScript` 加载的普通组件行为和 Embla 引擎，注册到宿主 Livewire 的 Alpine |
| editor.js | 完整 React / Tiptap Web Component，在首次使用时加载 |
| editor.css | 编辑器成品的工具栏、编辑区和编辑态样式，与编辑器脚本一起按需加载 |

Editor 默认由包内 Laravel 路由提供带内容哈希的生产文件。Blade `@assets` 在首次使用 Editor 时加载脚本和样式各一次，多个实例共享资源，各自拥有独立编辑器和 React root。[手动发布编辑器](content.md#资源加载与多个实例)

图标由 Blade 内联正式 SVG，不加载图标字体或图标服务。图片、音频、视频和附件的 URL 指向正文引用的媒体文件。

## 全量 CSS 大小

前后两次使用相同的 Tailwind CSS 4.3.3、主题和组件集合。最小宿主用 `source(none)` 导入整个库，只额外生成 `block` 和 `rounded-lg`。演示应用使用生产 Vite 构建，计入它自己的样式和工具类。表中单位为字节，gzip 按实际文件分别压缩后求和。

| 测量对象 | 原 CSS | 当前 CSS | 原 gzip | 当前 gzip |
| --- | ---: | ---: | ---: | ---: |
| 最小宿主，全量组件及只读 RichText | 440,263 | 324,964 | 55,051 | 44,328 |
| 同一宿主，另挂载 Editor | 440,263 | 352,008 | 55,051 | 50,143 |
| 演示应用的完整主样式 | 493,759 | 380,099 | 65,803 | 55,297 |
| 演示应用挂载 Editor 时的 CSS 合计 | 493,759 | 407,143 | 65,803 | 61,112 |

编辑器样式单独为 27,044 B，gzip 为 5,815 B。同页新增编辑器实例时复用已加载的文件。

## 服务端观测

以下为状态边界优化时的历史测量。要复测，在已安装依赖的本地演示应用目录运行 `php scripts/benchmark.php`。每项预热 3 次、测量 20 次，计时范围为纯计算或 Blade/Livewire 首次渲染。测量环境为 Windows、PHP 8.4.27、Laravel 13.42.0、Livewire 4.4.8，CLI OPcache 关闭，本机负载可影响结果。

| 场景 | 中位数 | p95 | 输出 |
| --- | ---: | ---: | --- |
| 2,000 个树节点校验与展平 | 3.479 ms | 4.040 ms | 2,000 行 |
| 10,000 个树节点校验与展平 | 15.035 ms | 19.144 ms | 10,000 行 |
| 200 个纯 Blade Button | 3.572 ms | 4.697 ms | 25,490 B HTML |
| 200 选项 Livewire Select 首次挂载 | 23.925 ms | 28.351 ms | 109,959 B HTML/snapshot |

树节点线性遍历；选择状态每次渲染先构建成员索引；大列表由调用方搜索/分页控制规模。200 选项输出已约 107 KiB，不能仅以 PHP 毫秒数判断页面开销，也不使用 Alpine 虚拟列表绕过 Blade 渲染边界。

## 运行时审查

- Popover 打开期间监听滚动、窗口和弹层尺寸、锚点可见性及 Livewire 重绘，并在动画帧中检查锚点矩形的变化。关闭或销毁时释放这些监听和动画帧。嵌套弹层按所属根定位和关闭。
- Modal 用共享 Set 管理滚动锁；Sidebar 桌面展示不使用会抢焦点的 show()。
- Carousel、MessageScroller、Sidebar、Editor 的观察器、键盘监听、React root 和引擎实例有销毁路径。
- Editor 的菜单 options 和扩展定义稳定复用；避免渲染触发新事务循环，空闲窗口的事务计数为零。
- Markdown 序列化按不可变 ProseMirror 内容块使用 WeakMap 缓存，未变内容不重复往返探测。
- RichText 缓存共享 schema、Markdown 转换器和清理器，内容仍在服务端处理并由 Blade 输出。
- 独立控件只负责自己的值；公共 slot 留在调用方，父级刷新、slot 动作及嵌套子组件身份有公共调用回归。

## 验收范围

PHP 测试逐个渲染公开根组件的最小调用，并覆盖当前值协议和数据约束。演示应用覆盖目录、分类及实际示例。静态检查包含 PHPStan level 8、Pint、Prettier 和 JS 架构边界规则。[公开组件目录](components.md)

本地演示的 Chromium 测试覆盖组件挂载、10 个分类的重复 id、运行时错误和移动布局，包含选择、日期范围、菜单、滑块、OTP、拖动、弹层、图片预览、调用方 slot 和编辑器生命周期。在演示目录运行 `npm run test:browser`，截图写入 `storage/app/ui-browser/`。2026-11-18 的视觉验收涵盖 1440/390px、浅色与深色、459 个官方实际示例、公开组合及动画采样。历史结果与比较条件见 [shadcn 组件覆盖](shadcn-coverage.md)。

内容验收比较 HTML → Markdown → HTML 三轮，再经过服务端 RichText 展示并重载。复杂排版、媒体、任务、表格、代码空白使用内嵌 HTML 保留。业务不允许或 schema 之外的内容按清理规则处理，不承诺任意网页源码原样保留。

发行工作流另外把生产 ZIP 安装到全新 Laravel 应用，构建宿主的 Tailwind CSS，再用 Chromium 验证样式覆盖、脚本加载、交互和父级更新。[开发与发布](development.md)

NumberInput、Slider、InputOtp、Editor、Cropper、Signature 直接绑定调用方。通用行为历史测量 gzip 为 24,478 B，当前资源大小由 `npm run build` 输出。各控件的前后测量与状态协议见 [状态边界](state-boundaries.md)。
