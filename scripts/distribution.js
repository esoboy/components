import {
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

const runtime = [
    'src',
    'routes',
    'lang',
    'resources/views',
    'resources/content/schema.json',
    'resources/icons/catalog.php',
    'resources/icons/LICENSE',
    'resources/dist/ui.js',
    'resources/dist/editor.js',
    'LICENSE',
]

function notices(inputs) {
    const packages = new Set(['tailwindcss'])
    for (const input of inputs) {
        const match = input.match(/^(.*node_modules\/(?:@[^/]+\/)?[^/]+)\//)
        if (match) packages.add(match[1].replace(/^node_modules\//, ''))
    }
    const texts = [
        'shadcn/ui\n' + readFileSync('resources/css/LICENSE.shadcn', 'utf8'),
        readFileSync('resources/js/editor/tiptap-ui/LICENSE', 'utf8'),
    ]
    for (const name of [...packages].sort()) {
        const directory = join('node_modules', name)
        const metadata = JSON.parse(readFileSync(join(directory, 'package.json'), 'utf8'))
        const files = readdirSync(directory).filter((file) =>
            /^(licen[sc]e|copying|notice)([.-].*)?$/i.test(file),
        )
        texts.push(
            [
                metadata.name + ' ' + metadata.version + ' (' + metadata.license + ')',
                ...files.sort().map((file) => readFileSync(join(directory, file), 'utf8')),
            ].join('\n'),
        )
    }
    return texts.join('\n\n') + '\n'
}

export async function assembleDistribution(inputs) {
    const destination = resolve('dist')
    const stage = mkdtempSync(resolve('.dist-'))
    const previous = stage + '-previous'
    try {
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
        const ref = process.env.GITHUB_REF_TYPE === 'tag' ? process.env.GITHUB_REF_NAME : 'HEAD'
        const docs =
            'https://github.com/esoboy/components/blob/' + encodeURIComponent(ref) + '/docs/'
        const readme = readFileSync('README.md', 'utf8').replaceAll('](docs/', '](' + docs)
        writeFileSync(join(stage, 'README.md'), readme)
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
