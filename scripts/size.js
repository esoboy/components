import { readFileSync } from 'node:fs'
import { gzipSync } from 'node:zlib'
import { compile, optimize } from '@tailwindcss/node'

// Keep the host and its utility candidates constant across releases.
const host = await compile(
    '@import "tailwindcss" source(none); @import "./dist/resources/css/ui.css";',
    { base: process.cwd(), onDependency() {} },
)
const stylesheet = optimize(host.build(['block', 'rounded-lg']), { minify: true }).code
const assets = {
    'Composer CSS source': readFileSync('dist/resources/css/ui.css'),
    'ui.css': Buffer.from(stylesheet),
}
for (const name of ['ui.js', 'editor.css', 'editor.js']) {
    assets[name] = readFileSync('dist/resources/dist/' + name)
}

const sizes = Object.fromEntries(
    Object.entries(assets).map(([name, bytes]) => [
        name,
        { bytes: bytes.length, gzip: gzipSync(bytes).length },
    ]),
)
for (const type of ['css', 'js']) {
    const main = sizes['ui.' + type]
    const editor = sizes['editor.' + type]
    sizes['all.' + type] = { bytes: main.bytes + editor.bytes, gzip: main.gzip + editor.gzip }
}
console.table(sizes)
