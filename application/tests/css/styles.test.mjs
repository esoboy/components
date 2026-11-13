import { test } from 'node:test'
import assert from 'node:assert/strict'
import { gzipSync } from 'node:zlib'
import { fileURLToPath } from 'node:url'
import { compile, optimize } from '@tailwindcss/node'
import { Scanner } from '@tailwindcss/oxide'
import { spawnSync } from 'node:child_process'
import { chromium } from 'playwright'
import { readFile, access } from 'node:fs/promises'

const base = fileURLToPath(new URL('../../resources/css/', import.meta.url))

async function build(entry) {
    const compiler = await compile(`@import "tailwindcss" source(none);\n@import "../../../package/resources/css/${entry}.css";\n@source "../../tests/css/controls.blade.php";`, { base, onDependency() {} })
    const scanner = new Scanner({ sources: compiler.sources })
    return optimize(compiler.build(scanner.scan()), { minify: true }).code
}

test('component groups include the styles used by their Blade composition', async () => {
    const aliases = { 'alert-dialog': 'dialog', sheet: 'dialog', confirm: 'dialog', textarea: 'input', 'toggle-group': 'toggle', heading: 'typography', text: 'typography', toaster: 'toast', 'tree-select': 'tree' }
    for (const entry of ['controls', 'overlays', 'pickers', 'display', 'navigation', 'data', 'trees']) {
        const dependencies = []
        const compiler = await compile(`@import "tailwindcss" source(none); @import "../../../package/resources/css/${entry}.css";`, { base, onDependency(path) { dependencies.push(path.replaceAll('\\', '/')) } })
        const scanner = new Scanner({ sources: compiler.sources })
        scanner.scan()
        const referenced = new Set()
        for (const file of scanner.files) {
            for (const match of (await readFile(file, 'utf8')).matchAll(/<x-ui::([\w-]+)/g)) referenced.add(aliases[match[1]] ?? match[1])
        }
        for (const component of referenced) {
            const path = new URL(`../../../package/resources/css/components/${component}.css`, import.meta.url)
            if (await access(path).then(() => true, () => false)) assert.ok(dependencies.some(path => path.endsWith(`/components/${component}.css`)), `${entry} is missing ${component}`)
        }
        assert.ok(compiler.build(scanner.scan()).includes('--color-background'), entry)
    }
})

test('basic controls build without unrelated component styles or template utilities', async () => {
    const css = await build('controls')
    for (const slot of ['button', 'input', 'field', 'checkbox', 'radio-group', 'switch']) assert.ok(css.includes(`data-slot=${slot}`), slot)
    for (const slot of ['editor', 'sidebar', 'calendar', 'tree']) assert.ok(!css.includes(`data-slot=${slot}`), slot)
    assert.ok(Buffer.byteLength(css) < 80000, `controls CSS: ${Buffer.byteLength(css)} bytes`)
    assert.ok(gzipSync(css).length < 12000, `controls gzip: ${gzipSync(css).length} bytes`)
})

test('modular controls preserve Nova states and utility overrides in light and dark themes', async () => {
    // Windows PHP installations may expose php.bat; both command and argument are fixed.
    const windows = process.platform === 'win32'
    const result = spawnSync(windows ? 'cmd.exe' : 'php', windows ? ['/d', '/s', '/c', 'php tests/css/render.php'] : ['tests/css/render.php'], { encoding: 'utf8', cwd: fileURLToPath(new URL('../../', import.meta.url)) })
    assert.equal(result.status, 0, result.stderr)
    const html = result.stdout.split('<!--css-fixture-->')[1]
    assert.ok(html)
    const [full, controls] = await Promise.all([build('ui'), build('controls')])
    const browser = await chromium.launch({ headless: true })
    try {
        const page = await browser.newPage({ viewport: { width: 800, height: 900 } })
        const capture = async css => {
            await page.setContent(`<html><head><style>${css}</style></head><body>${html}</body></html>`)
            const states = []
            for (const theme of ['light', 'dark']) {
                await page.locator('html').evaluate((element, value) => element.dataset.theme = value, theme)
                for (const state of ['default', 'hover', 'focus']) {
                    await page.mouse.move(790, 890)
                    await page.evaluate(() => document.activeElement?.blur())
                    if (state === 'hover') await page.locator('#outline').hover()
                    if (state === 'focus') await page.locator('#input').focus()
                    // Wait for the component's existing color transitions to settle.
                    await page.waitForTimeout(200)
                    states.push(await page.locator('[id]').evaluateAll(elements => Object.fromEntries(elements.map(element => {
                        const css = getComputedStyle(element)
                        return [element.id, Object.fromEntries(['height', 'width', 'padding', 'gap', 'fontSize', 'borderRadius', 'borderColor', 'backgroundColor', 'color', 'boxShadow', 'opacity'].map(property => [property, css[property]]))]
                    }))))
                }
            }
            return states
        }
        const expected = await capture(full)
        const actual = await capture(controls)
        assert.deepEqual(actual, expected)
        assert.equal(actual[0].button.height, '32px')
        assert.equal(actual[0].input.height, '32px')
        assert.equal(actual[0].checkbox.width, '16px')
        assert.equal(actual[0].custom.height, '40px')
        assert.equal(actual[0].custom.borderRadius, '0px')
        assert.equal(actual[0].disabled.opacity, '0.5')
    } finally {
        await browser.close()
    }
})
