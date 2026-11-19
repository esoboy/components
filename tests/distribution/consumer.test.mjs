import { test } from 'node:test'
import assert from 'node:assert/strict'
import { chromium } from 'playwright'

async function openProbe(t) {
    const browser = await chromium.launch()
    t.after(() => browser.close())
    const page = await browser.newPage({ reducedMotion: 'reduce' })
    page.setDefaultTimeout(7000)
    await page.goto(
        new URL('/ui-package-test', process.env.UI_TEST_URL ?? 'http://127.0.0.1:18744').href,
    )
    await page.waitForFunction(() => window.Livewire?.all().length > 0)
    return page
}

for (const id of ['single-choice', 'multi-choice']) {
    test(`a new results page preserves the search and selection in ${id}`, async (t) => {
        const page = await openProbe(t)
        const input = page.locator('#' + id)
        await input.fill('bet')
        await page.waitForFunction(() => document.querySelector('#filtered').value === 'bet')
        const other = id === 'single-choice' ? 'multi-choice' : 'single-choice'
        await page.waitForFunction((id) => {
            const root = document.getElementById(id).closest('[data-slot="combobox"]')
            return root.querySelectorAll('[role="option"]').length === 1
        }, other)
        assert.equal(await input.inputValue(), 'bet')
        if (id === 'multi-choice') {
            const chip = page.locator('[data-slot="combobox-chip"]')
            assert.equal((await chip.textContent()).trim(), 'Alpha')
            assert.deepEqual(
                await page.locator('#controls').evaluate((f) => new FormData(f).getAll('multi[]')),
                ['a'],
            )
            await chip.getByRole('button').click()
            await page.waitForFunction(() => document.querySelector('#chosen').value === '')
            assert.equal(await chip.count(), 0)
        } else {
            await input.press('Escape')
            await page.waitForFunction(
                () => document.querySelector('#single-choice').value === 'Alpha',
            )
            await page.locator('#new-assignee').click()
            await page.waitForFunction(
                () => document.querySelector('#single-choice').value === 'Beta',
            )
        }
    })
}

test('a changed date limit moves only an out-of-bounds month and preserves the submitted range', async (t) => {
    const page = await openProbe(t)
    await page.locator('#start-calendar').click()
    const calendar = page.locator('#booking')
    await calendar.getByRole('button', { name: 'Next month' }).click()
    for (const date of ['2027-07-12', '2027-07-15']) {
        await calendar.getByRole('button', { name: date }).click()
        await page.locator(`#booking-dates input[name="period[]"][value="${date}"]`).waitFor({
            state: 'attached',
        })
    }
    await page.locator('#limit-july').click()
    await page.waitForFunction(
        () => document.querySelector('#booking [aria-label="2027-07-09"]')?.disabled,
    )
    assert.equal(await calendar.getByRole('combobox', { name: 'Select month' }).inputValue(), '7')
    await page.locator('#limit-september').click()
    await calendar.getByRole('button', { name: '2027-09-15' }).waitFor()
    assert.equal(await calendar.getByRole('button', { name: 'Previous month' }).isDisabled(), true)
    assert.deepEqual(
        await page
            .locator('#booking-dates')
            .evaluate((form) => new FormData(form).getAll('period[]')),
        ['2027-07-12', '2027-07-15'],
    )
})

test('typing a number preserves an out-of-range prefix and clamps on commit', async (t) => {
    const page = await openProbe(t)
    const input = page.locator('#quantity')
    await input.focus()
    await input.press('ControlOrMeta+a')
    await input.press('2')
    assert.equal(await input.inputValue(), '2')
    await input.press('5')
    await input.press('Tab')
    assert.equal(
        await page.locator('#controls').evaluate((f) => new FormData(f).get('quantity')),
        '25',
    )
    await input.fill('3')
    await input.press('Tab')
    assert.equal(await input.inputValue(), '10')
    await input.fill('120')
    await input.press('Enter')
    assert.equal(await input.inputValue(), '99')
})

test('editor reset requests the initial output format and respects the controlled value', async (t) => {
    const page = await openProbe(t)
    await page.locator('#start-editing').click()
    await page.waitForFunction(() => document.querySelector('#second-editor')?.editor)
    await page.locator('#replace-body').click()
    await page.waitForFunction(() => document.querySelector('#second-editor').value === 'Second')
    await page.locator('#first-editor [contenteditable="true"]').fill('Edited')
    await page.locator('#documents').evaluate((form) => {
        form.addEventListener('value-changed', (event) => {
            const input = event.target.querySelector('esoboy-editor')
            form.dataset[input.id === 'first-editor' ? 'resetBody' : 'resetControlled'] =
                event.detail.value
        })
    })
    await page.locator('#reset-documents').click()
    await page.waitForFunction(
        () => document.querySelector('#documents').dataset.resetControlled !== undefined,
    )
    assert.deepEqual(
        await page.locator('#documents').evaluate((form) => ({
            event: form.dataset.resetBody,
            submitted: new FormData(form).get('body'),
            requested: form.dataset.resetControlled,
            controlled: new FormData(form).get('controlled'),
        })),
        {
            event: '<p><strong>First</strong></p>',
            submitted: '<p><strong>First</strong></p>',
            requested: '**First**',
            controlled: 'Second',
        },
    )
})

test('closed popovers ignore document keys and Escape closes the front open panel', async (t) => {
    const page = await openProbe(t)
    const reads = await page.locator('#popovers').evaluate((container) => {
        const roots = new Set(container.querySelectorAll('[data-ui-overlay]'))
        const original = Element.prototype.querySelectorAll
        let count = 0
        Element.prototype.querySelectorAll = function (selector) {
            if (roots.has(this) && selector === '[data-ui-panel]') count++
            return original.call(this, selector)
        }
        try {
            document.body.dispatchEvent(new KeyboardEvent('keydown', { key: 'a', bubbles: true }))
            return count
        } finally {
            Element.prototype.querySelectorAll = original
        }
    })
    assert.equal(reads, 0)
    await page.locator('#outer-trigger').press('Enter')
    await page.locator('#inner-trigger').press('Enter')
    assert.equal(await page.locator('#popovers :popover-open').count(), 2)
    await page.keyboard.press('Escape')
    await page.waitForFunction(
        () => document.querySelectorAll('#popovers :popover-open').length === 1,
    )
    assert.equal(await page.evaluate(() => document.activeElement.id), 'inner-trigger')
    await page.keyboard.press('Escape')
    await page.waitForFunction(() => !document.querySelector('#popovers :popover-open'))
    assert.equal(await page.evaluate(() => document.activeElement.id), 'outer-trigger')
})

test('the host stylesheet styles components and the first editor loads one shared bundle', async () => {
    const browser = await chromium.launch()
    try {
        const page = await browser.newPage()
        const errors = []
        const assets = []
        page.on('pageerror', (error) => errors.push(error.message))
        page.on('response', (response) => {
            const path = new URL(response.url()).pathname
            if (path.startsWith('/esoboy/ui/')) assets.push([path.split('/')[3], response.status()])
        })
        const base = process.env.UI_TEST_URL ?? 'http://127.0.0.1:18744'
        const response = await page.goto(new URL('/ui-package-test', base).href)
        assert.equal(response.status(), 200)
        await page.waitForFunction(() => window.Livewire?.all().length > 0)
        assert.deepEqual(assets, [['ui.js', 200]])
        assert.equal(
            await page.locator('svg[data-slot="icon"] circle').first().getAttribute('r'),
            '10',
        )
        assert.equal(
            await page.locator('#rename').evaluate((el) => getComputedStyle(el).display),
            'inline-flex',
        )
        assert.deepEqual(
            await page.locator('#custom-button').evaluate((el) => {
                const style = getComputedStyle(el)
                return [style.display, style.paddingLeft, style.paddingRight, style.borderRadius]
            }),
            ['block', '31px', '31px', '8px'],
        )
        assert.equal(
            await page.locator('#custom-title').evaluate((el) => getComputedStyle(el).fontWeight),
            '400',
        )
        assert.equal(
            await page.locator('#custom-prose').evaluate((el) => getComputedStyle(el).fontSize),
            '22px',
        )
        assert.equal(
            await page.locator('#large-spinner').evaluate((el) => getComputedStyle(el).width),
            '20px',
        )
        assert.equal(
            await page.locator('#fixed-panel').evaluate((el) => getComputedStyle(el).flexGrow),
            '0',
        )
        await page.locator('#choice').click()
        await page.getByRole('option', { name: 'One', exact: true }).click()
        await page.locator('#rename').click()
        await page.waitForFunction(
            () => document.querySelector('#caller-action').textContent.trim() === 'Updated',
        )
        await page.locator('#caller-action').click()
        await page.waitForFunction(() => document.querySelector('#calls').value === '1')
        await page.locator('#start-editing').click()
        await page.waitForFunction(
            () =>
                document.querySelectorAll('esoboy-editor').length === 2 &&
                [...document.querySelectorAll('esoboy-editor')].every((editor) => editor.editor),
        )
        const toolbar = page.locator('#first-editor .toolbar')
        assert.equal(await toolbar.evaluate((el) => getComputedStyle(el).display), 'grid')
        assert.equal(
            await toolbar.evaluate((el) => {
                el.classList.add('block')
                return getComputedStyle(el).display
            }),
            'block',
        )
        await page.locator('#stop-editing').click()
        await page.locator('esoboy-editor').first().waitFor({ state: 'detached' })
        await page.locator('#start-editing').click()
        await page.waitForFunction(() => document.querySelector('#second-editor')?.editor)
        assert.deepEqual(
            assets.filter(([name]) => name === 'editor.js'),
            [['editor.js', 200]],
        )
        assert.equal(await page.locator('script[src*="editor.js"]').count(), 1)
        assert.deepEqual(errors, [])
    } finally {
        await browser.close()
    }
})

test('an editor-only Blade page loads its own script and reuses it across Livewire navigation', async (t) => {
    const browser = await chromium.launch()
    t.after(() => browser.close())
    const page = await browser.newPage()
    page.setDefaultTimeout(7000)
    const scripts = []
    const errors = []
    page.on('pageerror', (error) => errors.push(error.message))
    page.on('request', (request) => {
        const path = new URL(request.url()).pathname
        if (path.startsWith('/esoboy/ui/')) scripts.push(path.split('/')[3])
    })
    const base = process.env.UI_TEST_URL ?? 'http://127.0.0.1:18744'
    await page.goto(new URL('/ui-package-test?editors=1', base).href)
    const second = page.locator('#document-two [contenteditable="true"]')
    assert.equal(await second.innerText(), 'Two')
    assert.deepEqual(scripts, ['editor.js'])
    assert.equal(await page.locator('script[src*="editor.js"]').count(), 1)
    await page.getByRole('link', { name: 'General components' }).click()
    // Alpine sets aria-expanded once Livewire has loaded the new page's head scripts.
    await page.locator('#choice[aria-expanded="false"]').click()
    await page.getByRole('option', { name: 'One', exact: true }).click()
    await page.getByRole('link', { name: 'Standalone editors' }).click()
    assert.equal(await second.innerText(), 'Two')
    assert.deepEqual(scripts, ['editor.js', 'ui.js'])
    assert.deepEqual(errors, [])
})
