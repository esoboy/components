# Issue tracker: Local Markdown

任务与规格保存在当前仓库的 .scratch/ 下。

## 文件约定

- 每项工作使用独立目录：.scratch/<feature-slug>/。
- 规格文件（需要时）：.scratch/<feature-slug>/spec.md。
- 每张工单一个文件：
  .scratch/<feature-slug>/issues/<NN>-<slug>.md。
- 编号从 01 开始，按依赖顺序排列，前置工单在先。
- 工单必须包含 What to build、Blocked by、Status 和验收清单。
- 分批任务增加 Batch 字段，批次顺序与技术依赖分别记录。
- 评论和执行记录追加到工单末尾，不覆盖既有记录。

## 状态与依赖

- ready-for-agent：已明确范围和验收要求，等待领取。
- claimed：已领取，正在执行。
- resolved：验收完成，并记录验证结果。
- Blocked by 列出前置工单的编号与标题；没有依赖时明确写 None。
- 只有全部前置工单均为 resolved，工单才可开始。
- 执行前先将状态改为 claimed。
- 完成后记录结果与验证证据，再将状态改为 resolved。

## 技能操作

- “发布到任务跟踪器”：创建独立工单文件。
- “读取工单”：读取对应文件的完整内容和执行记录。
- 不因发布子工单而关闭或修改来源工单。
