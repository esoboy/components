import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { compile, optimize } from '@tailwindcss/node'
import postcss from 'postcss'
import colors from 'tailwindcss/colors'

test('the distribution ships one Tailwind source and two standalone scripts', () => {
    const entries = (path) => readdirSync('dist/' + path).sort()
    assert.deepEqual(entries(''), [
        'LICENSE',
        'README.md',
        'THIRD-PARTY-LICENSES.txt',
        'composer.json',
        'lang',
        'resources',
        'routes',
        'src',
    ])
    assert.deepEqual(entries('resources'), ['boost', 'content', 'css', 'dist', 'icons', 'views'])
    assert.deepEqual(entries('resources/css'), ['palette.cjs', 'ui.css'])
    assert.deepEqual(entries('resources/dist'), ['editor.js', 'ui.js'])
    assert.deepEqual(entries('resources/icons'), ['LICENSE', 'catalog.php'])
    const notices = readFileSync('dist/THIRD-PARTY-LICENSES.txt', 'utf8')
    assert.ok(notices.includes(readFileSync('resources/css/LICENSE.shadcn', 'utf8').trim()))
    const manifest = JSON.parse(readFileSync('dist/composer.json', 'utf8'))
    const source = JSON.parse(readFileSync('composer.json', 'utf8'))
    for (const field of ['require', 'autoload', 'extra'])
        assert.deepEqual(manifest[field], source[field])
    for (const field of ['require-dev', 'autoload-dev', 'scripts'])
        assert.equal(field in manifest, false)
    const sourceCss = readFileSync('dist/resources/css/ui.css', 'utf8')
    assert.match(sourceCss, /@apply\b/)
    assert.match(sourceCss, /@theme\b/)
    assert.doesNotMatch(sourceCss, /@(import|tailwind|utility)\b/)
    for (const node of postcss.parse(sourceCss).nodes) {
        if (node.type === 'comment') continue
        assert.equal(node.type, 'atrule')
        if (node.name === 'plugin') {
            assert.match(node.params, /^['"]\.\/palette\.cjs['"]$/)
            continue
        }
        if (node.name === 'theme' || node.name === 'custom-variant') continue
        assert.equal(node.name, 'layer')
        assert.equal(node.params, 'components')
    }
})

test('the Composer package ships the Boost skill and its component reference', () => {
    const skill = 'resources/boost/skills/esoboy-ui-development'
    for (const file of ['SKILL.md', 'references/components.md']) {
        const path = `${skill}/${file}`
        assert.deepEqual(readFileSync(`dist/${path}`), readFileSync(path))
    }
})

test('the button has one declaration for each base utility', () => {
    const utilities = []
    const css = postcss.parse(readFileSync('dist/resources/css/ui.css', 'utf8'))
    css.walkRules('.ui-button', (rule) => {
        rule.walkAtRules('apply', (apply) => utilities.push(...apply.params.split(/\s+/)))
    })
    assert.ok(utilities.length > 0)
    assert.equal(new Set(utilities).size, utilities.length)
})

test('a host with source detection disabled still builds every component default', async () => {
    const compiler = await compile(
        '@import "tailwindcss" source(none); @import "./dist/resources/css/ui.css";',
        { base: process.cwd(), onDependency() {} },
    )
    const css = postcss.parse(
        optimize(compiler.build(['block', 'rounded-lg']), { minify: true }).code,
    )
    const utilities = new Set()
    let editorDisplay
    css.walkRules((rule) => {
        let layer = rule.parent
        while (layer && layer.name !== 'layer') layer = layer.parent
        if (layer?.params === 'utilities') utilities.add(rule.selector)
        if (
            rule.selector.includes('[data-slot') ||
            rule.selector.includes('esoboy-editor') ||
            rule.selector.includes('.ui-rich-content')
        ) {
            assert.equal(layer?.params, 'components', rule.selector)
        }
        if (rule.selectors.includes('esoboy-editor')) {
            rule.walkDecls('display', (decl) => {
                editorDisplay = decl.value
            })
        }
    })
    assert.deepEqual(utilities, new Set(['.block', '.rounded-lg']))
    assert.equal(editorDisplay, 'block')
})

test('the distributed names and SVGs match the pinned Lucide release', () => {
    const expected = Object.keys(
        JSON.parse(readFileSync('node_modules/lucide-static/icon-nodes.json', 'utf8')),
    ).sort()
    const catalog = readFileSync('dist/resources/icons/catalog.php', 'utf8')
    const entries = [...catalog.matchAll(/^    '([a-z0-9-]+)' => '(.+)',$/gm)]
    assert.deepEqual(
        entries.map(([, name]) => name),
        expected,
    )
    for (const [, name, markup] of entries) {
        const svg = readFileSync(`node_modules/lucide-static/icons/${name}.svg`, 'utf8')
        const contents = svg
            .match(/<svg\b[^>]*>([\s\S]*?)<\/svg>/)[1]
            .trim()
            .replace(/>\s+</g, '><')
            .replace(/\s+\/>/g, '/>')
        assert.equal(markup.replace(/\\(['\\])/g, '$1'), contents, name)
    }
})

test('the host palette includes every Tailwind shade and custom colors without scanning templates', async () => {
    const compiler = await compile(
        '@import "tailwindcss" source(none); @import "./dist/resources/css/ui.css"; @theme { --color-brand: #123456; --color-brand-500: #abcdef; } @theme inline { --color-local-brand: var(--local-ink); }',
        { base: process.cwd(), onDependency() {} },
    )
    const output = compiler.build([])
    const tokens = new Map()
    postcss.parse(output).walkRules((rule) => {
        if (!rule.selector.includes('data-ui-palette=')) return
        rule.walkDecls('--ui-palette-color', (decl) => {
            for (const [, name] of rule.selector.matchAll(/\[data-ui-palette="([^"]+)"\]/g))
                tokens.set(name, decl.value)
        })
    })
    for (const [family, shades] of Object.entries(colors)) {
        if (typeof shades !== 'object') continue
        for (const shade of Object.keys(shades)) {
            assert.ok(tokens.get(`${family}-${shade}`)?.includes(`--color-${family}-${shade},`))
        }
        assert.ok(tokens.get(family).includes(tokens.get(family + '-500')))
    }
    assert.match(tokens.get('brand'), /--color-brand, #123456/)
    assert.match(tokens.get('brand-500'), /--color-brand-500, #abcdef/)
    assert.match(tokens.get('local-brand'), /^var\(--local-ink[,)]/)
})
