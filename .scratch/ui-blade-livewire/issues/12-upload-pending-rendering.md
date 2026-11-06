# 12: 上传中的文件列表交给组件渲染

**What to build:** 待上传文件、名称、大小、进度文字及完成失败状态均由统一的组件渲染路径输出。

**Blocked by:** None (can start immediately)

**Status:** resolved

**Batch:** 2

**Audit coverage:** 原问题 7。

- [x] 不再逐文件克隆 pending 模板并填文字。
- [x] 拖放和本地图片即时预览可由 Alpine 处理。
- [x] 验证取消、失败、重试、多文件、文件移除及上传完成切换。
- [x] 更新公共调用示例和说明，通过相关测试并记录验证结果。

**验证记录**

8 个 PHP 测试与 2 个 Chromium 用例覆盖进度、多文件、取消、失败和重试。使用 Laravel 本地服务另跑真实 multipart 上传，确认文件完成后显示为正式附件并可移除。
