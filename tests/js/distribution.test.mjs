import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { compile, optimize } from '@tailwindcss/node'
import postcss from 'postcss'

test('the distribution ships the component stylesheet and standalone editor assets', () => {
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
    assert.deepEqual(entries('resources/css'), ['ui.css'])
    assert.deepEqual(entries('resources/dist'), ['editor.css', 'editor.js', 'ui.js'])
    assert.deepEqual(entries('resources/icons'), ['LICENSE', 'catalog.php'])
    const notices = readFileSync('dist/THIRD-PARTY-LICENSES.txt', 'utf8')
    assert.ok(
        notices.includes(readFileSync('resources/distribution/LICENSE.shadcn', 'utf8').trim()),
    )
    for (const name of ['embla-carousel', '@tiptap/core', 'react-dom', 'tailwindcss']) {
        assert.ok(notices.includes(`\n${name} `), `Missing third-party notice for ${name}`)
    }
    assert.deepEqual(readFileSync('dist/LICENSE'), readFileSync('resources/distribution/LICENSE'))
    const manifest = JSON.parse(readFileSync('dist/composer.json', 'utf8'))
    const source = JSON.parse(readFileSync('composer.json', 'utf8'))
    for (const field of ['require', 'autoload', 'extra'])
        assert.deepEqual(manifest[field], source[field])
    for (const field of ['require-dev', 'autoload-dev', 'scripts'])
        assert.equal(field in manifest, false)
    const sourceCss = readFileSync('dist/resources/css/ui.css', 'utf8')
    assert.match(sourceCss, /\.ui-rich-text\b/)
    assert.doesNotMatch(
        sourceCss,
        /esoboy-editor|\.ui-editor\b|--editor-|ProseMirror|\.tableWrapper/,
    )
    const editorCss = readFileSync('dist/resources/dist/editor.css', 'utf8')
    assert.match(editorCss, /esoboy-editor/)
    assert.match(editorCss, /\.document/)
    assert.doesNotMatch(editorCss, /\.ui-rich-text|@import\b/)
    for (const node of postcss.parse(editorCss).nodes.filter((node) => node.type !== 'comment')) {
        assert.equal(node.name, 'layer')
        assert.equal(node.params, 'components')
    }
    assert.match(sourceCss, /@apply\b/)
    assert.match(sourceCss, /@theme\b/)
    assert.doesNotMatch(sourceCss, /@(import|tailwind|utility)\b/)
    for (const node of postcss.parse(sourceCss).nodes) {
        if (node.type === 'comment') continue
        assert.equal(node.type, 'atrule')
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
    css.walkRules((rule) => {
        let layer = rule.parent
        while (layer && layer.name !== 'layer') layer = layer.parent
        if (layer?.params === 'utilities') utilities.add(rule.selector)
        if (
            rule.selector.includes('.ui-') ||
            rule.nodes.some((node) => node.prop === '--color-info')
        ) {
            assert.equal(layer?.params, 'components', rule.selector)
        }
    })
    assert.deepEqual(utilities, new Set(['.block', '.rounded-lg']))
})

test('component defaults resolve host theme values in either import order', async () => {
    const library = readFileSync('dist/resources/css/ui.css', 'utf8')
    const host = `@theme {
        --color-info: var(--brand-info);
        --text-body: 15px;
        --text-body--line-height: 1.8;
        --spacing-3: 21px;
        --gap-2: 19px;
        --padding-4: 23px;
        --radius-sm: 5px;
        --radius-lg: 17px;
        --shadow-card: var(--brand-shadow);
    }`
    for (const sources of [
        [host, library],
        [library, host],
    ]) {
        const compiler = await compile(
            ['@import "tailwindcss" source(none);', ...sources].join('\n'),
            {
                base: process.cwd(),
                onDependency() {},
            },
        )
        const css = postcss.parse(
            compiler.build(['bg-info', 'text-body', 'rounded-sm', 'rounded-lg']),
        )
        const root = new Map()
        css.walkRules((rule) => {
            if (rule.selector.includes(':host') && rule.parent.params === 'components')
                rule.walkDecls((decl) => root.set(decl.prop, decl.value))
        })
        assert.equal(root.get('--color-info'), 'var(--brand-info)')
        assert.equal(root.get('--text-body'), '15px')
        assert.equal(root.get('--radius-sm'), '5px')
        assert.equal(root.get('--radius-lg'), '17px')
        assert.equal(root.get('--shadow-card'), 'var(--brand-shadow)')
        for (const [selector, property, token] of [
            ['.ui-progress', 'gap', '--spacing-3'],
            ['.ui-picker-trigger', 'gap', '--gap-2'],
            ['.ui-alert-dialog-content', 'padding', '--padding-4'],
            ['.ui-card', 'line-height', '--text-body--line-height'],
        ]) {
            const declarations = []
            css.walkRules(selector, (rule) =>
                declarations.push(...rule.nodes.filter((node) => node.prop === property)),
            )
            assert.ok(
                declarations.some((d) => d.value.includes(token)),
                selector + ' ' + property,
            )
        }
    }
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
