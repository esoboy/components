import { build } from 'esbuild'
import { minify } from 'terser'
import { gzipSync } from 'node:zlib'
import { mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { basename } from 'node:path'
import { buildIcons } from './icons.js'
import { assembleDistribution } from './distribution.js'

// RichText and the editable document render the same HTML with separate CSS scopes.
const editorTypography = {
    name: 'editor-document',
    setup(build) {
        build.onLoad({ filter: /[/\\]_document\.css$/ }, ({ path }) => ({
            contents: readFileSync(path, 'utf8').replaceAll(
                '.ui-rich-text',
                ':where(esoboy-editor) .document',
            ),
            loader: 'css',
        }))
    },
}

const inputs = new Set()
mkdirSync('resources/dist', { recursive: true })
rmSync('resources/dist/ui.css', { force: true })

for (const [name, entry, budget] of [
    // Regular components share one entry, including the carousel engine.
    ['ui', 'resources/js/bootstrap.js', 25 * 1024],
    ['editor', 'resources/js/editor/editor.jsx', 600 * 1024],
]) {
    const result = await build({
        entryPoints: [entry],
        bundle: true,
        minify: name === 'editor',
        minifySyntax: true,
        format: 'iife',
        target: 'es2022',
        write: false,
        splitting: false,
        metafile: true,
        plugins: name === 'editor' ? [editorTypography] : [],
        define: { 'process.env.NODE_ENV': '"production"' },
        outfile: `resources/dist/${name}.js`,
        legalComments: 'inline',
    })
    const files = new Map(result.outputFiles.map((file) => [basename(file.path), file]))
    const file = files.get(`${name}.js`)
    const stylesheet = files.get('editor.css')
    if (!file || (name === 'editor' && !stylesheet) || files.size !== (name === 'editor' ? 2 : 1))
        throw new Error(`Unexpected output for ${name}.`)
    const minified = await minify(file.text, {
        ecma: 2022,
        module: true,
        compress: { passes: 2, hoist_vars: name === 'ui' },
    })
    const contents = Buffer.from(minified.code)
    const gzip = gzipSync(contents).length
    if (gzip > budget) throw new Error(`${name}: ${gzip} / ${budget} gzip bytes.`)
    const imports = Object.values(result.metafile.outputs).flatMap((output) => output.imports)
    if (imports.some(({ kind, path }) => kind !== 'url-token' || !path.startsWith('data:')))
        throw new Error('Runtime external imports are forbidden.')
    writeFileSync(file.path, contents)
    if (stylesheet) writeFileSync(stylesheet.path, stylesheet.contents)
    for (const input of Object.keys(result.metafile.inputs)) inputs.add(input)
    console.log(
        JSON.stringify({
            module: name,
            javascript: contents.length,
            gzip,
            budget,
            css: stylesheet?.contents.length,
        }),
    )
}

buildIcons()
await assembleDistribution(inputs)
