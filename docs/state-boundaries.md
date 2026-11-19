# Livewire 状态边界

2026-11-17，按用户确认完成 NumberInput、Slider、InputOtp、Editor、Cropper、Signature 的状态精简，并清理复杂选择组件的内部状态。

## 三种不同的边界

1. 纯 Blade 输出 HTML，原生表单或浏览器引擎通过 wire:model 接入调用方，不产生独立服务端实例。
2. 嵌套 Livewire 有自己的状态、snapshot 和生命周期；通过 Modelable / Reactive 共享数据。
3. Livewire 4 的 @island 是同一组件模板内的局部渲染区域，复用所属组件的状态，不等于创建子组件，也不限于页面。

来源：[Livewire Islands](https://livewire.laravel.com/docs/4.x/islands)、[嵌套组件](https://livewire.laravel.com/docs/4.x/nesting)。包内没有 @island、wire:island 或 #[Isolate]。

## 本库实现

NumberInput、Slider、InputOtp、Editor、Cropper、Signature 已改为 Blade 入口和浏览器适配，累计删除六个内部 Livewire 类及视图。值直接绑定所属 Livewire，不修改框架 hooks、memo，不向父级添加隐藏状态，也不保留旧内部动作。控件自己的 value-changed 是浏览器事件，由项目决定是否调用服务器。

普通绑定保留父级权威值；单向 value 需要父级接受；initial-value 只初始化一次，浏览器输入缓冲支持原生 FormData/reset。相关修饰符、稳定身份、父级保存验证及受控事件写法见 [状态与组合](state-and-composition.md)。Blade 仍渲染滑块数量、OTP 格子、内容提示及调用方 slot；Editor 的 React/Tiptap 渲染例外与共享内容定义不变。Signature 按用户授权由 canvas 绘制完整笔画；Blade 负责容器、清空按钮和表单字段，详见 ADR 0007。

13 类内部实例为 Select、Combobox、Command、Questionnaire、TagsInput、Calendar、DatePicker、TimePicker、Tree、TreeSelect、Cascader、Transfer、Mentions，负责服务端选项、筛选、导航或流程。DatetimePicker 由一个 DatePicker 实例管理完整日期时间，复用 TimePicker 的 Blade 面板和时间运算。Select 与 data-table.page-size 继续保持 Base UI 弹层、键盘与隐藏表单字段体验。Tree 系列的树数据只使用 nodes；Cascader 不再携带 expanded；Transfer 固定多选，不再序列化 multiple/placeholder。共享选择与搜索逻辑抽离后，各组件只声明自身使用的配置。Questionnaire 所有题目共用一个实例，部件为 Blade。不要为基础组件统一添加 @island；拥有昂贵渲染区域的页面或 Livewire 组件应按实际测量选择局部渲染。

## 前一轮四个简单控件的本机前后测量

Windows / PHP 8.4.27，CLI OPcache 关闭。同一脚本每项预热 3 次、测量 10 次，比较同一公开控件首次 Blade 渲染；包含控件 HTML、挂载和序列化，不含 HTTP 或浏览器交互。前后测量不在同一时刻，受本机负载影响，不视为生产时延承诺。

| 20 个控件 | 精简前 PHP 中位数 | 精简后 PHP 中位数 | HTML 前 → 后 | 内部实例前 → 后 | 原始 snapshot 前 → 后 |
| --- | ---: | ---: | ---: | ---: | ---: |
| NumberInput | 56.105 ms | 27.321 ms | 45,910 → 36,200 B | 20 → 0 | 9,810 → 0 B |
| InputOtp | 44.116 ms | 11.839 ms | 89,220 → 71,810 B | 20 → 0 | 10,470 → 0 B |
| Slider | 41.989 ms | 10.019 ms | 41,110 → 25,420 B | 20 → 0 | 10,990 → 0 B |

Editor 同样不再创建内部实例；复杂文档的主要客户端开销仍是每个 Web Component 的编辑器引擎，不承诺消除其成本。共享 JS 仍只引入一次完整编辑器文件。

表中数据比较同一个控件在状态精简前后的结果。各控件输出的 HTML 和交互能力不同。

## 公共调用验收

Chromium 的 `/scenarios/native-controls` 同时挂载绑定、镜像、受控、非受控与修饰符示例，仅页面和调用方 slot counter 两个 Livewire 实例；请求中没有内部值控件。验证默认延迟提交、父级回填、数组数量变化、同值多实例、受控接受/拒绝、非受控重绘保留、只读/禁用及销毁重挂载。

`/scenarios/native-form` 是没有任何 Livewire 状态实例的 Blade 表单，验证数值步进、范围滑块、OTP、两个独立编辑器、裁剪与签名、FormData、reset 和资源单次加载。完整编辑器往返、工具栏和原有 89 个组件场景仍参加全量 Chromium 回归。

`/scenarios/complex-controls` 验证 Cropper / Signature 的父级绑定、镜像、受控接受与拒绝、非受控重绘保留、回填、只读与禁用和 slot。签名连续两笔加一次裁剪微调在默认绑定下产生 0 次更新请求。Mentions 连续三次普通输入从 3 次请求降为 0 次，不同 @query 仍请求服务器渲染候选。Cascader 覆盖初始定位、浏览保留、父级回填、空 nodes 后加载及节点迁移。用例位于本地演示应用的 `tests/browser/complex-controls.test.mjs`。
