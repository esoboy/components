/*
 * Bundles the package's JavaScript into dist/, which the asset route serves and
 * vendor:publish --tag=ui-assets copies to public/vendor/ui.
 *
 *   npm run build   minified bundles, their .gz/.br, and manifest.json
 *   npm run dev     unminified, rebuilt on every change
 */

import * as esbuild from 'esbuild'
import { createHash } from 'node:crypto'
import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { brotliCompressSync, constants, gzipSync } from 'node:zlib'

const watch = process.argv.includes('--watch')
const root = fileURLToPath(new URL('..', import.meta.url))
const dist = join(root, 'dist')

const bundles = [
    // Every component but the heavy ones, as one classic script for @uiScripts.
    { entryPoints: { ui: 'resources/js/ui.js' }, format: 'iife' },

    // Heavy components: modules their views load through @assets, with what they
    // import on demand (a Shiki language, …) split into chunks/.
    {
        entryPoints: { 'code-block': 'resources/js/heavy/code-block.js' },
        format: 'esm',
        splitting: true,
        chunkNames: 'chunks/[name]-[hash]',
    },
]

// Writes a build's files with their compressed copies, then lists every dist file with its version.
const output = {
    name: 'dist',
    setup(build) {
        build.onEnd(async result => {
            if (result.errors.length) return

            await Promise.all(result.outputFiles.map(async ({ path, contents }) => {
                await mkdir(dirname(path), { recursive: true })
                await writeFile(path, contents)
                await writeFile(`${path}.gz`, gzipSync(contents, { level: 9 }))
                await writeFile(`${path}.br`, brotliCompressSync(contents, { params: { [constants.BROTLI_PARAM_QUALITY]: 11 } }))
            }))

            await writeManifest()

            if (watch) console.log(`[esoboy/ui] built ${result.outputFiles.length} file(s)`)
        })
    },
}

async function writeManifest() {
    const files = (await readdir(dist, { recursive: true }))
        .map(file => file.replaceAll('\\', '/'))
        .filter(file => /\.(js|css|json)$/.test(file) && file !== 'manifest.json')
        .sort()

    const manifest = {}

    for (const file of files) {
        manifest[file] = createHash('sha256').update(await readFile(join(dist, file))).digest('hex').slice(0, 12)
    }

    await writeFile(join(dist, 'manifest.json'), `${JSON.stringify(manifest, null, 4)}\n`)
}

await rm(dist, { recursive: true, force: true })

for (const bundle of bundles) {
    const options = {
        ...bundle,
        absWorkingDir: root,
        outdir: dist,
        bundle: true,
        minify: ! watch,
        write: false,
        logLevel: 'warning',
        plugins: [output],
    }

    if (watch) {
        await (await esbuild.context(options)).watch()
    } else {
        await esbuild.build(options)
    }
}
