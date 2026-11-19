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
const intro =
    '# 组件目录\n\n全部公开组件使用 `x-ui::`。以下最小调用均由包的公共渲染测试执行。状态契约见 [状态与组合](state-and-composition.md)。\n\n'
writeFileSync(
    resolve(root, 'docs/components.md'),
    intro +
        names.map((name) => `## ${name}\n\n\`\`\`blade\n${examples[name]}\n\`\`\`\n`).join('\n'),
)
console.log(`Verified ${names.length} entries and synchronized the component documentation.`)
