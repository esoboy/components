import { existsSync, readFileSync, readdirSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'

const root = resolve(import.meta.dirname, '..')
const examples = JSON.parse(readFileSync(resolve(root, 'docs/examples.json'), 'utf8'))
const names = Object.keys(examples).sort()
const publicNames = readdirSync(resolve(root, 'resources/views/components'), {
    withFileTypes: true,
})
    .filter(
        (entry) =>
            entry.isFile() ||
            existsSync(resolve(root, 'resources/views/components', entry.name, 'index.blade.php')),
    )
    .map((entry) => entry.name.replace('.blade.php', ''))
    .sort()
if (JSON.stringify(names) !== JSON.stringify(publicNames))
    throw new Error('Every public component must have one documented example.')
const coveragePath = resolve(root, 'docs/shadcn-coverage.md')
const coverage = readFileSync(coveragePath, 'utf8').replaceAll('\r\n', '\n')

// The detailed tables own the classification; examples.json owns the public names.
function componentRows(heading) {
    const section = coverage.split(`\n## ${heading}\n`)[1].split('\n## ')[0]
    return section
        .split('\n')
        .filter((line) => line.startsWith('| '))
        .slice(2)
        .map((row) => {
            const cells = row.split('|').slice(1, -1)
            return {
                label: cells[0].trim(),
                status: cells.at(-1).trim(),
                components: [
                    ...row.matchAll(/\[([\w-]+)\]\(\.\.\/resources\/views\/components\//g),
                ].map((match) => match[1]),
            }
        })
}

const official = componentRows('官网完整对应表')
const extensions = componentRows('本库扩展').flatMap((row) => row.components)
const foundations = componentRows('基础封装').flatMap((row) => row.components)
const partial = official.filter((row) => row.status.startsWith('部分对应')).map((row) => row.label)
const missing = official.filter((row) => row.components.length === 0).map((row) => row.label)
const mapped = new Set(
    official.flatMap((row) => row.components).filter((name) => !foundations.includes(name)),
)
const classified = [...mapped, ...extensions, ...foundations].sort()
if (JSON.stringify(classified) !== JSON.stringify(names)) {
    throw new Error('The shadcn coverage tables must classify every public component exactly once.')
}

const summary = [
    '| 统计口径 | 数量 |',
    '| --- | ---: |',
    `| shadcn Components 目录项 | ${official.length} |`,
    `| 本库公开根组件 | ${names.length} |`,
    `| shadcn 有直接对应入口 | ${official.length - partial.length - missing.length} |`,
    `| shadcn 部分对应 | ${countWithNames(partial)} |`,
    `| shadcn 缺失 | ${countWithNames(missing)} |`,
    `| 本库扩展根组件 | ${extensions.length} |`,
    `| 本库基础封装根组件 | ${countWithNames(foundations)} |`,
].join('\n')
function countWithNames(items) {
    return items.length ? `${items.length}（${items.join('、')}）` : '0'
}

const intro =
    '# 组件目录\n\n全部公开组件使用 `x-ui::`。以下最小调用均由包的公共渲染测试执行。状态契约见 [状态与组合](state-and-composition.md)。\n\n'
writeFileSync(
    resolve(root, 'docs/components.md'),
    intro +
        names.map((name) => `## ${name}\n\n\`\`\`blade\n${examples[name]}\n\`\`\`\n`).join('\n'),
)
writeFileSync(
    coveragePath,
    coverage.replace(/## 统计\n.*?(?=\n## )/s, '## 统计\n\n' + summary + '\n'),
)
console.log(`Verified ${names.length} public components and synchronized the catalog and coverage.`)
