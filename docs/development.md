# 开发、构建与发布

开发仓库的根目录就是组件包。`src/`、`routes/`、`lang/`、`resources/views/` 和内容定义由 Laravel 在运行时使用。`resources/css/`、`resources/js/`、`scripts/` 和 `tests/` 用于开发和验收。相邻的 `../application/` 是本地演示，位于 Git 工作树之外。

在现有工作区中从 `package/` 运行 Git 和构建命令。全新克隆后直接进入克隆目录即可。迁移前的提交和标签保留原目录结构，例如 `git show v0.7.1:package/composer.json` 可以查看该版本的清单。

## 开发环境

在仓库根目录使用 PHP 8.4 或 8.5、Composer 2 和 Node 22 或更新版本运行以下命令。

```sh
composer install
npm ci
npm run build
npm test
npm run format:check
composer lint -- --test
composer analyse
composer test
```

`npm run build` 合并 `resources/css/ui.css` 的本地导入，保留供宿主 Tailwind 编译的指令，并用 esbuild 构建通用组件脚本和完整 Editor。图标由锁定版本 `lucide-static/icon-nodes.json` 的正式名称与节点生成，PHP 直接按名称读取图形。更新 Lucide 时同步更新 `lucide-static`、`lucide-react` 和 npm 锁文件，并运行包测试。选用图标时可浏览 [Lucide 官方目录](https://lucide.dev/icons/)。

`docs/examples.json` 是公共调用样例的来源。修改样例后运行 `npm run docs` 生成[组件目录](components.md)。Pest 逐项渲染这些调用。包构建和 CI 都可以从单独检出的开发仓库运行。

## 生产包

```sh
npm run archive
```

`dist/` 是可直接安装的 Composer 包，`esoboy-ui.zip` 是同一内容的归档。构建脚本按以下清单组装生产包。

| 文件 | 用途 |
| --- | --- |
| `composer.json`、`src/`、`routes/` | 运行依赖、包发现、PHP 类和资源路由 |
| `resources/views/`、`lang/`、`resources/content/schema.json` | Blade 渲染、翻译及内容契约 |
| `resources/css/ui.css` | Tailwind 源文件，包含所有组件和编辑器的样式 |
| `resources/dist/ui.js`、`resources/dist/editor.js` | 通用组件行为和按需加载的完整编辑器 |
| `resources/icons/catalog.php` | 正式 Lucide 图标的 SVG 内容 |
| `README.md`、`LICENSE`、`resources/icons/LICENSE`、`THIRD-PARTY-LICENSES.txt` | 安装说明和随包许可 |

宿主在 Tailwind 入口导入库样式，通过 `@uiScript` 加载通用脚本并启动 Livewire / Alpine。Editor 出现在页面时加载一次专用脚本。接入示例见[使用说明](../README.md)。

## 发布与验证

推送 `v*` 标签触发 GitHub Actions。CI 在 PHP 8.4 和 8.5 上验证开发库，将生产 ZIP 安装到全新的 Laravel 项目，由该项目构建 Tailwind CSS 并缓存视图和路由。Chromium 验证样式覆盖、公开组件、调用方 slot、父级重绘及多实例 Editor 的请求次数。

发布任务复用验收过的 ZIP，将其内容提交到 `esoboy/ui` 的 `main`，再创建同名标签。重跑已有标签时核对文件树。GitHub 的 `UI` 环境需要配置有发行仓库写权限的 `UI_SPLIT_TOKEN`。[验证工作流](../.github/workflows/package.yml) · [发布工作流](../.github/workflows/release.yml)

本机若已有演示应用，先在开发库运行 `npm run build`，再从演示目录运行 Composer。演示通过 path repository 指向 `../package/dist`，可以直接检查生产包的实际表现。
