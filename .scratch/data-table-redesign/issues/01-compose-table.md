# 01: 页面组合普通数据表格

## What to build

页面传入记录和列定义，用一层循环和原生行插槽组合表格。

## Blocked by

None (can start immediately)

**Status** resolved

## 验收清单

- [x] 默认单元格读取字段，空数据展示默认空态，操作列可以省略。
- [x] 自定义单元格和操作列取得当前记录，按钮调用页面 Livewire 方法。
- [x] 在公开 Blade 调用中验证列定义、行循环和空态。

验证记录。Feature 测试通过公开 Blade 调用验证字段、原生行插槽与页面操作绑定。组合浏览器验收由 08 覆盖。
