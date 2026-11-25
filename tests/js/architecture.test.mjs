import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { resolve } from 'node:path'
import * as lucide from 'lucide-react'
import postcss from 'postcss'
import { registerUi } from '../../resources/js/ui.js'

const root = resolve(import.meta.dirname, '../..')
const files = (dir) =>
    readdirSync(dir, { withFileTypes: true }).flatMap((entry) =>
        entry.isDirectory() ? files(resolve(dir, entry.name)) : [resolve(dir, entry.name)],
    )

test('the CSS entry imports every component source and the token file owns the theme', () => {
    const styles = resolve(root, 'resources/css')
    const visited = new Set()
    function visit(file) {
        if (visited.has(file)) return
        visited.add(file)
        const css = postcss.parse(readFileSync(file, 'utf8'))
        if (file === resolve(styles, 'ui.css'))
            assert.ok(css.nodes.every((node) => node.name === 'import' || node.type === 'comment'))
        css.walkAtRules('theme', () => assert.equal(file, resolve(styles, 'token.css')))
        css.walkAtRules('import', (rule) => {
            const target = rule.params.match(/^['"]([^'"]+)['"]/)[1]
            visit(resolve(file, '..', target))
        })
    }
    visit(resolve(styles, 'ui.css'))
    assert.deepEqual(visited, new Set(files(styles).filter((file) => file.endsWith('.css'))))
})

test('editor controls import official Lucide names', () => {
    for (const file of files(resolve(root, 'resources/js/editor'))) {
        const source = readFileSync(file, 'utf8')
        for (const match of source.matchAll(/import\s*\{([^}]+)\}\s*from\s*'lucide-react'/g)) {
            for (const specifier of match[1].split(',')) {
                const name = specifier.trim().split(/\s+/)[0]
                if (name) assert.equal(lucide[name]?.displayName, name, file + ': ' + name)
            }
        }
    }
})

test('each component selector and state has one source for its declarations', () => {
    const componentClasses = new Map()
    const styles = [
        ...files(resolve(root, 'resources/css')),
        ...files(resolve(root, 'resources/js/editor')),
    ]
    for (const file of styles.filter((file) => file.endsWith('.css'))) {
        const css = postcss.parse(readFileSync(file, 'utf8'), { from: file })
        const selectors = new Set()
        const normalize = (text) => text.trim().replace(/\s+/g, ' ')
        css.walk((node) => {
            if (node.type !== 'rule' && node.name !== 'variant') return
            const where = file + ':' + node.source.start.line
            const context = []
            for (let parent = node.parent; parent.type !== 'root'; parent = parent.parent)
                context.unshift(
                    normalize(parent.selector ?? '@' + parent.name + ' ' + parent.params),
                )
            if (node.type === 'rule') {
                const names = node.selectors.map(normalize).sort()
                const key = JSON.stringify([...context, names])
                assert.ok(!selectors.has(key), where + ' repeats ' + node.selector)
                selectors.add(key)
                for (const name of names.filter(
                    (s) => !context.length && /^\.ui-[\w-]+$/.test(s),
                )) {
                    assert.ok(
                        !componentClasses.has(name),
                        where + ' repeats ' + name + ' from ' + componentClasses.get(name),
                    )
                    componentClasses.set(name, where)
                }
            }
            const properties = node.nodes
                .filter((child) => child.type === 'decl')
                .map((d) => d.prop)
            const apply = node.nodes.filter((child) => child.name === 'apply')
            const variants = node.nodes
                .filter((child) => child.name === 'variant')
                .map((v) => normalize(v.params))
            assert.equal(new Set(variants).size, variants.length, where + ' repeats a state')
            assert.equal(new Set(properties).size, properties.length, where + ' repeats a property')
            assert.ok(
                apply.length <= 1 && !(apply.length && properties.length),
                where + ' splits its declarations',
            )
            const utilities = apply.flatMap((a) => postcss.list.space(a.params))
            assert.equal(new Set(utilities).size, utilities.length, where + ' repeats a utility')
        })
    }
})

test('registration is explicit and reusable without importing a second runtime', () => {
    const data = new Set(),
        directives = new Set()
    const alpine = {
        data(name) {
            assert.ok(!data.has(name))
            data.add(name)
        },
        directive(name) {
            assert.ok(!directives.has(name))
            directives.add(name)
        },
    }
    registerUi(alpine)
    registerUi(alpine)
    assert.ok(data.has('uiPopover') && data.has('uiSignature'))
    assert.equal(data.has('uiEditor'), false)
    assert.equal(directives.size, 2)
})

test('the standalone editor, rich-text renderer and drawing controls keep their public boundaries', () => {
    const read = (name) => readFileSync(resolve(root, name), 'utf8')
    for (const file of files(resolve(root, 'resources/js/editor')).filter((file) =>
        /\.(jsx?|tsx?)$/.test(file),
    )) {
        assert.doesNotMatch(readFileSync(file, 'utf8'), /\b(?:Alpine|Livewire|\$wire)\b/, file)
    }
    const richText = read('resources/views/components/rich-text/index.blade.php')
    assert.doesNotMatch(richText, /@assets|wire:|x-data|<script\b|esoboy-editor/)
    for (const component of ['cropper', 'signature']) {
        assert.doesNotMatch(
            read('resources/js/components/' + component + '.js'),
            /\$wire|value-changed|wire:model/,
        )
    }
})

test('browser adapters do not generate templates or perform application requests', () => {
    for (const file of files(resolve(root, 'resources/js'))) {
        const source = readFileSync(file, 'utf8')
        if (/[\\/]editor[\\/]/.test(file)) {
            assert.doesNotMatch(source, /\bfetch\s*\(|XMLHttpRequest|import\s*\(/, file)
            continue
        }
        assert.doesNotMatch(
            source,
            /innerHTML|outerHTML|insertAdjacentHTML|replaceChildren|new\s+Option\s*\(|createElement|document\.write|\bfetch\s*\(|XMLHttpRequest/,
            file,
        )
        assert.doesNotMatch(source, /import\s*\(/, file)
    }
})

test('Blade owns content and behavior slots do not invent controls', () => {
    for (const file of files(resolve(root, 'resources/views'))) {
        const source = readFileSync(file, 'utf8')
        assert.doesNotMatch(source, /\bx-(?:html|text|for)\s*=|@props\([^\n]*['"]as['"]/, file)
        if (/[\\/](trigger|close)\.blade\.php$/.test(file)) {
            assert.match(source, /\$slot/, file)
            assert.doesNotMatch(source, /<(?:button|input)\b|<x-ui::button/, file)
        }
        for (const tag of source.matchAll(/<x-ui::(?:[^>"']|"[^"]*"|'[^']*')*>/g))
            assert.doesNotMatch(tag[0], /wire:click="[^"\n]*@js\(/, file)
    }
})

test('Table renders presentation and DataTable owns the interactive grid', () => {
    const views = resolve(root, 'resources/views')
    for (const file of files(resolve(views, 'components/table'))) {
        assert.doesNotMatch(
            readFileSync(file, 'utf8'),
            /Livewire|wire:|x-(?:on:|data|model)|DataTable|\$selected|\$rows|\$columns/,
            file,
        )
    }
    const grid = readFileSync(resolve(views, 'partials/data-table/grid.blade.php'), 'utf8')
    const table = readFileSync(resolve(views, 'components/data-table/index.blade.php'), 'utf8')
    assert.match(grid, /<x-ui::table\.head\s/)
    assert.match(grid, /<x-ui::table\.cell\s/)
    assert.match(table, /<x-ui::pagination\s/)
})

test('the package contains no upload endpoints, services or compatibility layers', () => {
    for (const file of files(resolve(root, 'src'))) {
        assert.doesNotMatch(
            readFileSync(file, 'utf8'),
            /WithFileUploads|TemporaryUploadedFile|Http::|Storage::|SupportSlots|SupportNestingComponents|addPersistentMiddleware/,
            file,
        )
    }
})
