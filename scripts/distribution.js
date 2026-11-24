import {
    copyFileSync,
    cpSync,
    existsSync,
    mkdirSync,
    mkdtempSync,
    readFileSync,
    readdirSync,
    renameSync,
    rmSync,
    writeFileSync,
} from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import postcss from 'postcss'
import postcssImport from 'postcss-import'

const materials = 'resources/distribution'
const runtime = [
    'src',
    'routes',
    'lang',
    'resources/boost',
    'resources/views',
    'resources/content/schema.json',
    'resources/icons/catalog.php',
    'resources/icons/LICENSE',
    'resources/dist/ui.js',
    'resources/dist/editor.js',
    'resources/dist/editor.css',
]

function notices(inputs) {
    const directories = new Set([resolve('node_modules/tailwindcss')])
    for (const input of inputs) {
        const match = resolve(input)
            .replaceAll('\\', '/')
            .match(/^(.*\/node_modules\/(?:@[^/]+\/)?[^/]+)\//)
        if (match) directories.add(resolve(match[1]))
    }
    const texts = [
        'shadcn/ui\n' + readFileSync(join(materials, 'LICENSE.shadcn'), 'utf8'),
        readFileSync('resources/js/editor/tiptap-ui/LICENSE', 'utf8'),
    ]
    const packages = [...directories].map((directory) => {
        const metadata = JSON.parse(readFileSync(join(directory, 'package.json'), 'utf8'))
        const files = readdirSync(directory).filter((file) =>
            /^(licen[sc]e|copying|notice)([.-].*)?$/i.test(file),
        )
        return [
            metadata.name + ' ' + metadata.version + ' (' + metadata.license + ')',
            ...files.sort().map((file) => readFileSync(join(directory, file), 'utf8')),
        ].join('\n')
    })
    return [...texts, ...new Set(packages.sort())].join('\n\n') + '\n'
}

export async function assembleDistribution(inputs) {
    const destination = resolve('dist')
    const stage = mkdtempSync(resolve('.dist-'))
    const previous = stage + '-previous'
    try {
        for (const name of ['README.md', 'LICENSE']) {
            copyFileSync(join(materials, name), join(stage, name))
        }
        for (const path of runtime) {
            const target = join(stage, path)
            mkdirSync(dirname(target), { recursive: true })
            cpSync(path, target, { recursive: true })
        }
        // Inline the local stylesheets while preserving Tailwind directives for the host build.
        const entry = 'resources/css/ui.css'
        const stylesheet = await postcss([postcssImport()]).process(readFileSync(entry, 'utf8'), {
            from: entry,
        })
        mkdirSync(join(stage, dirname(entry)), { recursive: true })
        writeFileSync(join(stage, entry), stylesheet.css)
        const development = JSON.parse(readFileSync('composer.json', 'utf8'))
        const manifest = {}
        for (const key of [
            'name',
            'description',
            'keywords',
            'type',
            'license',
            'require',
            'autoload',
            'extra',
        ]) {
            manifest[key] = development[key]
        }
        writeFileSync(join(stage, 'composer.json'), JSON.stringify(manifest, null, 4) + '\n')
        writeFileSync(join(stage, 'THIRD-PARTY-LICENSES.txt'), notices(inputs))
        if (existsSync(destination)) renameSync(destination, previous)
        try {
            renameSync(stage, destination)
        } catch (error) {
            if (existsSync(previous)) renameSync(previous, destination)
            throw error
        }
        rmSync(previous, { recursive: true, force: true })
    } finally {
        rmSync(stage, { recursive: true, force: true })
    }
    console.log('Built the Composer package in dist/.')
}
