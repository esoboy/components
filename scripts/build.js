import { build } from 'esbuild'
import { gzipSync } from 'node:zlib'
import { mkdirSync, rmSync, writeFileSync } from 'node:fs'
import { buildIcons } from './icons.js'
import { assembleDistribution } from './distribution.js'

const inputs = new Set()
mkdirSync('resources/dist', { recursive: true })
rmSync('resources/dist/ui.css', { force: true })

for (const [name, entry, budget] of [
    // Nova's carousel uses the same bundled Embla engine; retain one regular JS entry.
    ['ui', 'resources/js/bootstrap.js', 24 * 1024],
    ['editor', 'resources/js/editor/editor.jsx', 600 * 1024],
]) {
    const result = await build({
        entryPoints: [entry],
        bundle: true,
        minify: true,
        format: 'iife',
        target: 'es2022',
        write: false,
        splitting: false,
        metafile: true,
        define: { 'process.env.NODE_ENV': '"production"' },
        outfile: `resources/dist/${name}.js`,
        legalComments: 'inline',
    })
    if (result.outputFiles.length !== 1) throw new Error(`${name} must be a complete single file.`)
    const file = result.outputFiles[0],
        gzip = gzipSync(file.contents).length
    if (gzip > budget) throw new Error(`${name}: ${gzip} / ${budget} gzip bytes.`)
    if (Object.values(result.metafile.outputs).some((output) => output.imports.length))
        throw new Error('Runtime external imports are forbidden.')
    writeFileSync(file.path, file.contents)
    for (const input of Object.keys(result.metafile.inputs)) inputs.add(input)
    console.log(JSON.stringify({ module: name, javascript: file.contents.length, gzip, budget }))
}

buildIcons()
await assembleDistribution(inputs)
