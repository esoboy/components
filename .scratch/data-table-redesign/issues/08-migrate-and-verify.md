# 08: 迁移公开示例并整体验收

## What to build

应用演示和公开文档使用最终接口，验证各子组件在同一页面协同。

## Blocked by

- 04 页面筛选工具栏与一键清除
- 05 列布局、固定列与汇总行
- 07 树状表格与独立展开

**Status** resolved

## 验收清单

- [x] 普通分页表和树表演示覆盖新调用，升级说明和开发指南同步。
- [x] 被替代的接口直接迁移清理。
- [x] 回归覆盖父页面更新后的插槽和页面 Livewire action 归属，以及筛选、分页、列布局和选择联动。

验证记录。Data Table 的 37 项 Feature 和 12 项 Chromium 回归通过。JavaScript 全量 195 项通过，Data Table 范围的 PHPStan 通过。PHP 全量 1818 项通过，编辑器演示页现有 Alpine 指令导致 1 项架构测试失败。全量 PHPStan 仍报告 EditorControls 和 RemoteImage 的 4 个既有问题。
