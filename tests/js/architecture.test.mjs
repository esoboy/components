import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { resolve } from 'node:path'
import * as lucide from 'lucide-react'
import { registerUi } from '../../resources/js/ui.js'

const root = resolve(import.meta.dirname, '../..')
const files = (dir) =>
    readdirSync(dir, { withFileTypes: true }).flatMap((entry) =>
        entry.isDirectory() ? files(resolve(dir, entry.name)) : [resolve(dir, entry.name)],
    )

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

test('browser adapters do not generate templates or perform application requests', () => {
    for (const file of files(resolve(root, 'resources/js'))) {
        const source = readFileSync(file, 'utf8')
        if (/[\\/]editor[\\/]/.test(file)) {
            assert.doesNotMatch(source, /\bfetch\s*\(|XMLHttpRequest|import\s*\(/, file)
            continue
        }
        assert.doesNotMatch(
            source,
            /innerHTML|outerHTML|insertAdjacentHTML|createElement|document\.write|\bfetch\s*\(|XMLHttpRequest/,
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
