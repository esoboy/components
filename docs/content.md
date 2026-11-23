# 内容与浏览器交互契约

## Editor 与 RichText

Editor 使用 Tiptap v3（锁定 3.34.0）和 React 封装为 `<esoboy-editor>` Web Component。HTML 与 Markdown 都进入同一个所见即所得编辑区。RichText 在服务端解析、清理，再由 Blade 输出 HTML，不加载编辑器。

两端共用 `resources/content/schema.json` 定义的节点、标记、属性和安全样式。正文样式随 `resources/css/ui.css` 提供。输入支持 HTML 和 Markdown 字符串。React / Tiptap 管理每个 `<esoboy-editor>` 元素内的工具栏、菜单和文档。编辑器界面的 CSS 限定在该元素内。外部 toolbar、默认 slot、加载状态及业务动作由调用方 Blade / Livewire 管理。

升级时，如果自动化测试或业务脚本曾用 `editor.shadowRoot.querySelector(...)` 访问工具栏或编辑区，请改用 `editor.querySelector(...)`。

| 属性 | 组件 | 默认值 | 含义 |
| --- | --- | --- | --- |
| input-type | Editor、RichText | auto | html / markdown / auto；指定输入解析格式 |
| output-type | Editor | html | html / markdown，独立于输入格式 |
| upload-pasted-images | Editor | true | 粘贴图片时自动交给应用上传，可用 `:upload-pasted-images="false"` 在单个编辑器中关闭 |
| wire:model / value / initial-value | Editor | null | 绑定、单向受控、一次性初值三选一，值为字符串或空值 |
| value | RichText | 空字符串 | HTML / Markdown 字符串 |
| toolbar-preset | Editor | full | basic 基础文本工具 / full 全部保留工具；只改变操作入口，不改变内容能力 |
| disabled / readonly | Editor | false | 禁止文档修改；允许只读查找和查看 |
| id / name / aria-* | Editor | — | id 标识 Web Component；ARIA 传给实际 textbox；name 生成原生表单隐藏字段 |

`auto` 只按开头是否为 HTML 标签识别。**已知格式时显式指定 input-type，尤其是以 HTML 开头的混合 Markdown。** 输入/输出格式在 Blade 实例生命周期内固定，切换时使用新 key。保存内容再次载入时，input-type 应与保存格式一致。

```blade
<x-ui::editor id="article" wire:model="content" input-type="markdown" output-type="markdown" />
<x-ui::editor id="conversion" initial-value="**Hello**" input-type="markdown" output-type="html" />
<x-ui::rich-text :value="$content" input-type="markdown" />
```

输出格式在首次挂载、父级重载后立即生效，无需先编辑。普通 wire:model 不为每次按键发起服务器请求；需要即时父级回显时用 wire:model.live.debounce.300ms。value 为单向受控值，通过 value-changed 请求父级更新，父级拒绝后恢复外部权威值。Editor 没有内部 Livewire 实例，直接绑定调用方属性；公开 slot 仍属于调用方。非受控初值保存在编辑器，支持 name / FormData 与原生 form reset；同页实例独立，父级重绘不重建编辑器。受控事件通过 x-on:value-changed="$wire.requestContent($event.detail.value)" 调用父方法；保存前验证由项目负责，见 [状态与组合](state-and-composition.md)。

## 内容能力与操作入口

| 能力 | 编辑入口 / 展示 |
| --- | --- |
| 段落、H1–H6、引用、分隔线、硬换行 | 段落选择与工具栏；RichText 语义 HTML |
| 粗体、斜体、下划线、删除线、行内代码、上下标 | 格式工具栏、选区气泡菜单 |
| 字体、字号、行高、文本颜色、背景色/高亮、文本对齐 | 样式工具栏；保留允许的内联样式 |
| 无序、有序、嵌套、任务列表和编号格式 | 工具栏直接切换列表。在有序列表内打开「更多段落工具」可修改编号格式 |
| 普通代码块 | 从「更多段落工具」插入，可在同一菜单修改代码语言 |
| 链接 | 编辑 URL、打开方式、移除链接；自动识别及粘贴链接 |
| 表格 | 网格选取行列数或输入自定义尺寸。表内浮动工具支持选择、增删和移动行列、合并拆分、切换表头、整表删除与列宽拖动 |
| 图片、视频 | 各占独立内容块。选中后点击「媒体尺寸」选择预设，也可拖动两侧手柄、设置对齐、编辑标题和替代文本或更换文件 |
| 音频、文件 | 原生音频播放器支持循环和静音。可下载附件展示标题、文件名、扩展名和已知大小 |
| iframe 嵌入 | 在完整工具栏点击「嵌入 iframe」，粘贴第三方播放器地址或平台提供的 iframe 代码。点击播放器上方的标题可编辑或删除嵌入 |
| 折叠内容 | details / summary，编辑与只读端均可展开 |
| Emoji 内容展示 | Unicode 文字和已存在的 emoji 节点保留；没有候选菜单、选择器或触发按钮 |
| 内容块拖动 | 拖动块旁的手柄移动段落 |
| 查找替换 | 点击放大镜打开弹层。回车跳到下一处，Shift + 回车跳到上一处。可逐项或全部替换，也可启用大小写、全词和正则选项 |
| HTML 源码 | 点击工具栏右侧的「HTML 源码」在原编辑区域编辑当前文档的 HTML。点击「返回」查看排版，只读编辑器也可切换并复制源码 |
| 编辑辅助 | 本地撤销/重做、占位提示、选区/焦点、排版输入规则、字符/词数、目录、唯一标题 ID、缩放 |

不包含 Ruby、代码语法高亮、数学公式、Mention 节点、React/Vue DragHandle 适配器、协作、批注、修订、版本历史及商业能力（AI、DOC/PDF 导入导出等）。普通本地撤销/重做属于当前编辑会话，不是版本历史。独立 Mentions 组件不受编辑器 Mention 排除项影响。

斜杠菜单及触发入口、表情候选及选择器、不可见字符显示和空行浮动插入菜单均已移除。

## 工具栏预设

`toolbar-preset="basic"` 提供撤销/重做、正文/H1–H6、粗体/斜体/下划线/删除线/行内代码、清除格式、链接、无序/有序列表、引用和 HTML 源码编辑。`toolbar-preset="full"` 为默认值。上排直接调整字体、字号、行高、上下标和颜色，下排提供段落对齐、列表、插入与文档工具。窄屏按组换行。

固定工具栏和浮动工具栏用淡色标出光标处生效的格式。选中一段文字时，工具栏显示选区共有的格式。选中表格单元格、图片或视频时，还可看到对应的表头、尺寸或对齐设置。

源码模式始终编辑 HTML。输入时按共享内容规则更新文档，可以直接提交表单或执行调用方的保存操作。`output-type` 决定保存 HTML 还是 Markdown。点击「返回」即可查看排版，再次打开源码会显示该文档的规范 HTML。父级重新载入、受控值回填和表单重置也会更新源码区。

「清除格式」作用于所选内容。只有光标时清理当前文字块，全选时清理整篇。它沿用 Tiptap 的文字与段落格式清除，并移除选区标签的内联 `style`，包括表格、列和单元格。已设置的列宽以 HTML 属性保存，整个操作可一次撤销。

两种预设共享完整内容 schema；basic 仍能读取、展示和保存已有的表格、媒体与复杂格式。切换预设不会清空内容，也不是内容权限控制。预设支持父级更新，同页实例独立，仍只引入一个完整 editor.js。

```blade
<x-ui::editor id="summary" wire:model="summary" toolbar-preset="basic" />
<x-ui::editor id="article" wire:model="article" toolbar-preset="full" />
```

## 往返保真

Markdown 允许内嵌 HTML。仅当 Markdown 再解析后完全恢复支持的节点及属性，才输出纯 Markdown；其他内容使用内嵌 HTML。颜色、字体、对齐、标题 ID、合并单元格、任务列表、折叠、媒体、复杂嵌套列表和代码空白不会为了纯 Markdown 而静默丢失。

验收链路为 HTML → 编辑器 → Markdown → 编辑器 → HTML，重复三轮，再经服务端 RichText 展示并重新导入对比。比较支持的文档内容与属性，不要求标签别名、属性顺序、空元素写法或原始源码逐字一致。超出共享 schema 的标签/属性、脚本、事件属性、不安全 URL 和 CSS 会被清理；清理不是对任意网页 HTML 的无损承诺。单次输入上限为 1,000,000 UTF-8 字节。

服务端使用 CommonMark GFM 与 Symfony HtmlSanitizer，清理规则来自共享定义。项目负责媒体文件来源、上传、授权和访问策略。

### 第三方 iframe 播放器

在「嵌入 iframe」内粘贴平台分享菜单中的 iframe 代码，或输入可嵌入的 HTTP(S) 播放器地址。例如 YouTube 的 `https://www.youtube.com/embed/M7lc1UVf-VE`，也可直接粘贴爱奇艺提供的播放器嵌入代码。平台需允许该地址嵌入到当前站点。可编辑播放器标题，代码中的有效像素宽高会保留，窄屏按比例缩放。[YouTube 官方播放器示例](https://developers.google.com/youtube/player_parameters)

Editor 和 RichText 保留 `src`、标题和尺寸，并统一设置延迟加载、全屏、引用来源和沙箱权限。其他输入属性及 iframe 内联文档会被清理。通用播放器以隔离来源运行，YouTube 和爱奇艺的官方 HTTPS 播放器可使用各自的来源。HTML 和 Markdown 都可保存和重新载入嵌入。

## 单文件加载与多个实例

包内的 `resources/dist/editor.js` 包含编辑器的 Alpine 绑定、React、Tiptap 及其扩展。Blade 的 `@assets` 在页面首次使用 Editor 时引入一次脚本。默认由 Laravel 路由提供文件，URL 带有内容哈希，升级后自动使用当前版本。每个 Web Component 拥有自己的 React root、Tiptap 实例、选区、菜单和事件，销毁时释放实例。

需要由静态服务器托管组件资源时，可手动执行以下命令。

```sh
php artisan vendor:publish --tag=ui-assets --force
```

命令将 `ui.js` 和 `editor.js` 复制到 `public/vendor/esoboy/ui/`。当副本与包内文件一致时，Editor 使用该静态资源的 URL。升级后若副本过期，Editor 会从包内加载新版本，再次发布即可恢复静态托管。

## 媒体选择

点击媒体图标或「更换文件」后，先选择文件来源。「选择文件」从当前编辑器派发可冒泡的 `editor-media-select` 事件，应用可打开素材库或系统选择器，完成上传后回填可持久访问的 URL。「远程文件」打开地址、标题和替代文本表单，每次插入或更换一个文件。演示应用在 `/components/editor` 使用真实多文件选择器和调用方 Livewire 的临时上传。

从应用一次选取多个文件时，编辑器按数组顺序逐项插入。图片和视频各自占一行，编辑器和只读 RichText 使用相同的展示方式。

在富文本区粘贴截图、图片文件或带图片的 HTML，会自动派发同一个事件，`source` 为 `paste`。剪贴板同时提供图片文件与 HTML 时，优先使用文件，上传一次。编辑器在应用返回后一次插入整段图文，使用新文件的地址、标题和尺寸，保留原图的替代文本。上传失败或取消时仍保留随图片粘贴的文字。关闭自动上传后，HTML 图片遵循普通粘贴规则，应用仍可处理其他种类的文件及拖放。

「媒体尺寸」提供 sm、md、lg、原始尺寸和全宽。sm、md、lg 分别占所在正文区域宽度的 25%、50%、75%，全宽占 100%。「原始尺寸」优先使用应用返回的文件宽高，远程媒体缺少这些信息时使用浏览器读取的固有尺寸。素材最大宽度受正文区域限制。拖动手柄或在设置中修改像素宽高会切换为自定义尺寸。HTML 和 Markdown 以 `data-display-size` 保存选项，RichText 自带对应样式。

| 事件 detail | 含义 |
| --- | --- |
| `kind` | `image`、`audio`、`video` 或 `file`。混合拖放或粘贴请求使用 `file` |
| `multiple` | 总是 `true`，包括更换文件 |
| `source`、`files` | 点击本地选择时为 `picker` 和空数组。`paste` / `drop` 提供可直接上传的 `File[]`，常见的内嵌 Base64 图片也会转为文件 |
| `items` | 按粘贴顺序排列的 `(File \| string)[]`。只有图片地址时，该项为地址字符串，由应用读取或导入。拖放时各项都是文件，本地选择时为空 |
| `resolve(files)` | 按返回顺序插入。一项也使用数组，取消返回 `[]` |
| `reject(error)` | 显示此次选择或上传的错误 |

调用方用 `preventDefault()` 接管请求。下面的 `mediaLibrary` 方法由应用实现。`upload` 接收文件，`importImage` 接收图片地址，两者都返回一项规范文件数据。`Promise.all` 保持回填顺序，包括一次粘贴多张图片的情况。

```blade
<div x-data x-on:editor-media-select.prevent="
    const { kind, multiple, source, items, resolve, reject } = $event.detail;
    const result = source === 'picker'
        ? mediaLibrary.choose({ kind, multiple })
        : Promise.all(items.map(item => typeof item === 'string'
            ? mediaLibrary.importImage(item)
            : mediaLibrary.upload(item)));
    result.then(resolve, reject);
">
    <x-ui::editor id="body" wire:model="body" />
</div>
```

| 返回字段 | 类型与单位 | 适用文件 |
| --- | --- | --- |
| name、url、title | 文件名、HTTP(S) 或站内相对地址、标题字符串 | 全部 |
| size、type、ext | 字节数、MIME 类型、扩展名（例如 mp4） | 全部 |
| width、height | 文件原始宽高，单位为像素。编辑器用作初始展示尺寸，拖动后分别保存原始尺寸和展示尺寸 | 图片、视频 |
| seconds | 非负秒数，可含小数 | 音频、视频 |

数值元数据未知时可给 `null`。文件工具将所选文件作为附件插入。粘贴或拖放的一批文件按各项 MIME 类型选择节点。替代文本在远程表单和媒体设置里编辑。视频、音频和附件的替代文本通过 HTML 的 `aria-label` 保存。

```js
resolve([{
    name: 'lake.mp4', url: '/media/lake.mp4', title: '湖畔片段',
    size: 832400, type: 'video/mp4', ext: 'mp4',
    width: 1280, height: 720, seconds: 12.5,
}])
```

更换时，首项替换选中的文件，并保留其替代文本与对齐方式。其他项依序插在其后。等待应用返回期间，插入位置随正文编辑移动。切换文档、卸载编辑器、切换为只读或删除替换目标会使该次回填失效。

固定工具栏、列表、格式、对齐和历史按钮采用 Tiptap 官方 MIT UI 源码适配。图片与视频共享 Tiptap ResizableNodeView，表格使用 TableKit 和 ProseMirror 表格命令，选区工具栏由 Tiptap BubbleMenu 定位。[Tiptap UI 源码](https://github.com/ueberdosis/tiptap-ui-components/tree/799929bea4804c73767562b69f8acc2acdb8ac86)

## 其他内容组件

| 组件 | 数据与能力 |
| --- | --- |
| Image | 用 `src` 和 `alt` 展示图片 |
| MediaPreview | `src` 指定文件地址，`type` 取 `image`、`audio` 或 `video`。默认插槽的内容触发预览，可直接放文字、缩略图或按钮组件。图片可缩放和旋转，音频和视频使用原生播放控件，关闭时暂停。 |
| Cropper | src 与归一化 {x,y,width,height}；拖动、缩放、键盘微调，项目执行实际裁剪 |
| Signature | 归一化笔画列表；所有笔画由浏览器 canvas 绘制；不上传或生成文件 |
| CodeBlock | 转义代码、语言标签、复制，无着色引擎 |
| QrCode | PHP 编码、Blade SVG，不访问外部服务 |
| Mentions | 文本末尾 @query 匹配 options，选择后插入 @value；不是 Tiptap Mention |
| Attachment | 已有附件信息与动作的展示容器 |

旧版 `<x-ui::image-preview>` 调用改为 `<x-ui::media-preview type="image">`，保留 `src` 和可选的 `alt`。将原 `<x-slot:trigger>` 中的内容直接放入默认插槽。[三种媒体的调用示例](components.md#media-preview)

Toast / Toaster 内容由调用方提供，计时仅控制显示。Tour 步骤由 Blade 提供，Alpine 控制定位和焦点。弹层使用原生 Popover / Dialog API，本次验收浏览器为 Chromium。

Cropper / Signature 均无内部 Livewire；使用 wire:model 直接连接调用方，value 为单向受控，initial-value 只初始化浏览器缓冲，name 提供 JSON 格式的原生表单字段。受控请求通过 x-on:value-changed="$wire.requestValue($event.detail.value)" 交给父级。Cropper 空值显示默认区域 {x:0.1,y:0.1,width:0.8,height:0.8}，width/height 限制为 0.02–1；所有坐标须为有限数值，区域限制在图片内。Signature 空值显示空画布，最多 200 笔，每笔 1–4096 个归一化点；支持连续绘制、取消、清空、尺寸/主题变化重绘与表单 reset。调用方保存前验证笔画/坐标和业务权限；可复用 Support\DrawingValue 做结构检查。

Mentions 保留服务端正文和候选状态；普通输入不主动刷新候选。文本末尾 @query 改变时防抖 200 ms 请求，候选继续由 Blade 输出。同词复用候选，离开触发词隐藏列表，服务端拒绝过期查询词的选择；显式 wire:model.live 和受控父级操作仍按调用方要求发请求。
