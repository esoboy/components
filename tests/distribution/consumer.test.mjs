import { test } from 'node:test'
import assert from 'node:assert/strict'
import { chromium } from 'playwright'

async function openProbe(t, query = '', options = {}) {
    const browser = await chromium.launch()
    t.after(() => browser.close())
    const page = await browser.newPage({ reducedMotion: 'reduce', ...options })
    page.setDefaultTimeout(7000)
    page.setDefaultNavigationTimeout(30000)
    await page.goto(
        new URL('/ui-package-test' + query, process.env.UI_TEST_URL ?? 'http://127.0.0.1:18744')
            .href,
    )
    if (query === '?editors=1')
        await page.waitForFunction(
            () => document.querySelector('esoboy-editor')?.editor?.isInitialized,
        )
    else await page.waitForFunction(() => window.Livewire?.all().length > 0)
    return page
}

test('explicit statuses recolor existing variants and root utilities reach their parts', async (t) => {
    const page = await openProbe(t, '?status=1')
    await page.addStyleTag({ content: '*, *::before, *::after { transition: none !important }' })
    for (const dark of [false, true]) {
        const paint = await page.evaluate((dark) => {
            document.documentElement.classList.toggle('dark', dark)
            const style = (selector) => getComputedStyle(document.querySelector(selector))
            const pair = (s) => [s.backgroundColor, s.color]
            const swatch = document.createElement('span')
            document.body.append(swatch)
            const palette = (name, foreground = name + '-foreground') => {
                swatch.style.backgroundColor = 'var(--color-' + name + ')'
                swatch.style.color = 'var(--color-' + foreground + ')'
                return pair(getComputedStyle(swatch))
            }
            const primary = palette('primary')
            const muted = palette('muted')[0]
            const fieldWidth = (id, root) => {
                const trigger = document.getElementById(id)
                return [trigger.offsetWidth, trigger.closest(root).offsetWidth]
            }
            const ink = (name) => {
                swatch.style.color = 'var(--color-' + name + ')'
                return getComputedStyle(swatch).color
            }
            const shade = dark ? '600' : '500'
            const neutralText = dark ? 'white' : 'secondary-foreground'
            const defaults = {
                neutral: palette('secondary', neutralText),
                info: palette('blue-' + shade, 'white'),
                success: palette('green-' + shade, 'white'),
                warning: palette('yellow-' + shade, 'white'),
                error: palette('red-' + shade, 'white'),
            }
            const statuses = Object.entries(defaults).map(([name, expected]) => {
                const token = name === 'neutral' ? 'secondary' : name
                swatch.style.color =
                    'var(--color-' + token + (name === 'neutral' ? '-foreground)' : '-ink)')
                const ink = getComputedStyle(swatch).color
                const track = document.getElementById('switch-' + name).closest('.ui-switch')
                return {
                    name,
                    expected,
                    semantic: palette(token, name === 'neutral' ? neutralText : undefined),
                    ink,
                    solid: pair(style('#solid-' + name)),
                    badge: pair(style('#badge-' + name)),
                    softText: style('#soft-' + name).color,
                    outline: [
                        style('#outline-' + name).color,
                        style('#outline-' + name).borderTopColor,
                    ],
                    switchColors: [
                        getComputedStyle(track).backgroundColor,
                        getComputedStyle(track.querySelector('.ui-switch-thumb')).backgroundColor,
                    ],
                }
            })
            const alert = [
                style('#colored-alert').borderTopColor,
                style('#colored-alert .ui-icon').color,
                style('#colored-alert .ui-alert-title').color,
                style('#colored-alert .ui-alert-description').color,
            ]
            const expectedAlert = [
                ink('success-ink'),
                ink('success-ink'),
                ink('card-foreground'),
                ink('muted-foreground'),
            ]
            const toast = [
                style('#colored-toast').borderTopColor,
                style('#colored-toast .ui-icon').color,
            ]
            const warningInk = ink('warning-ink')
            swatch.remove()
            return {
                statuses,
                primary,
                alert,
                expectedAlert,
                toast,
                warningInk,
                off: ['plain-switch', 'info-switch-off'].map(
                    (id) =>
                        getComputedStyle(document.getElementById(id).closest('.ui-switch'))
                            .backgroundColor,
                ),
                widths: [
                    fieldWidth('theme-select', '.ui-select'),
                    fieldWidth('theme-picker', '.ui-date-picker'),
                ],
                base: pair(style('#base-button')),
                secondary: pair(style('#secondary-button')),
                neutralSoft: pair(style('#soft-neutral')),
                nested: pair(style('#nested-badge')),
                inner: pair(style('#inner-badge')),
                customBorder: style('#custom-outline').borderTopColor,
                avatarBadge: style('#avatar-badge').backgroundColor,
                avatarFallback: style('#avatar-fallback').backgroundColor,
                muted,
                customText: style('#custom-outline').color,
                infoText: style('#outline-info').color,
                progress: [style('progress').backgroundColor, style('progress').color],
                select: [
                    style('#theme-select').backgroundColor,
                    style('#theme-select').color,
                    style('.ui-select-content').backgroundColor,
                    style('.ui-select-option[aria-selected="true"]').color,
                ],
                picker: [
                    style('#theme-picker').backgroundColor,
                    style('.ui-date-picker-panel').backgroundColor,
                ],
                fields: [
                    style('#theme-password').color,
                    getComputedStyle(document.getElementById('theme-password'), '::placeholder')
                        .color,
                    style('#theme-menu-item').color,
                    style('.ui-transfer .ui-card').backgroundColor,
                    style('.ui-tags-input .ui-badge').backgroundColor,
                ],
            }
        }, dark)
        for (const status of paint.statuses) {
            assert.deepEqual(status.semantic, status.expected, status.name)
            assert.deepEqual(status.solid, status.expected, status.name)
            assert.deepEqual(status.badge, status.expected, status.name)
            assert.deepEqual(status.switchColors, status.expected, status.name)
            assert.deepEqual(status.outline, [status.ink, status.ink], status.name)
            assert.equal(status.softText, status.ink, status.name)
        }
        assert.deepEqual(paint.base, paint.primary)
        assert.deepEqual(paint.nested, paint.primary)
        assert.deepEqual(paint.inner, paint.primary)
        assert.deepEqual(paint.alert, paint.expectedAlert)
        assert.deepEqual(paint.toast, [paint.warningInk, paint.warningInk])
        assert.equal(paint.off[0], paint.off[1])
        for (const [trigger, root] of paint.widths) assert.equal(trigger, root)
        assert.deepEqual(paint.neutralSoft, paint.secondary)
        assert.equal(paint.customBorder, 'rgb(18, 52, 86)')
        assert.equal(paint.avatarBadge, 'rgb(36, 86, 71)')
        assert.equal(paint.avatarFallback, paint.muted)
        assert.equal(paint.customText, paint.infoText)
        assert.deepEqual(paint.progress, ['rgb(237, 237, 237)', 'rgb(35, 69, 103)'])
        assert.deepEqual(paint.select, [
            'rgb(239, 222, 219)',
            'rgb(54, 84, 98)',
            'rgb(239, 241, 252)',
            'rgb(86, 52, 18)',
        ])
        assert.deepEqual(paint.picker, ['rgb(236, 237, 239)', 'rgb(218, 241, 239)'])
        assert.deepEqual(paint.fields, [
            'rgb(33, 53, 72)',
            'rgb(86, 124, 152)',
            'rgb(80, 36, 52)',
            'rgb(239, 246, 251)',
            'rgb(239, 234, 188)',
        ])
    }
    await page.locator('#theme-menu-trigger').click()
    const item = page.locator('#theme-menu-item')
    await item.hover()
    assert.deepEqual(
        await item.evaluate((el) => [
            getComputedStyle(el).backgroundColor,
            getComputedStyle(el).color,
        ]),
        ['rgb(242, 235, 206)', 'rgb(89, 67, 18)'],
    )
    const overrides = await page.evaluate(() => {
        const colors = document.documentElement.style
        const bg = (id) => getComputedStyle(document.getElementById(id)).backgroundColor
        const destructiveBefore = bg('destructive-button')
        colors.setProperty('--color-secondary', '#253647')
        colors.setProperty('--color-error', '#710627')
        const result = [
            bg('solid-neutral'),
            bg('secondary-button'),
            bg('solid-error'),
            bg('destructive-button') === destructiveBefore,
        ]
        colors.setProperty('--color-destructive', '#203244')
        return [...result, bg('solid-error'), bg('destructive-button') !== destructiveBefore]
    })
    const drawing = page.locator('#theme-signature canvas')
    const readInk = (canvas) => canvas.getContext('2d').strokeStyle
    assert.equal(await drawing.evaluate(readInk), '#24795c')
    await drawing.evaluate((canvas) => {
        canvas.closest('.ui-signature').style.setProperty('--ink', '#453682')
    })
    await page.waitForFunction(
        () =>
            document.querySelector('#theme-signature canvas').getContext('2d').strokeStyle ===
            '#453682',
    )
    assert.deepEqual(overrides, [
        'rgb(37, 54, 71)',
        'rgb(37, 54, 71)',
        'rgb(113, 6, 39)',
        true,
        'rgb(113, 6, 39)',
        true,
    ])
})

test('grouped button corners and focused invalid controls retain their state styles', async (t) => {
    const page = await openProbe(t, '?status=1')
    await page.addStyleTag({ content: '.ui-checkbox { transition: none }' })
    await page.keyboard.press('Tab')
    const colors = await page.locator('#invalid-focus').evaluate((checkbox) => {
        checkbox.focus()
        return [false, true].map((dark) => {
            document.documentElement.classList.toggle('dark', dark)
            return getComputedStyle(checkbox).borderTopColor
        })
    })
    assert.deepEqual(colors, ['rgb(33, 105, 83)', 'rgb(33, 105, 83)'])
    const corners = await page.locator('#grouped-small').evaluate((button) => {
        const style = getComputedStyle(button)
        return [style.borderTopLeftRadius, style.borderTopRightRadius]
    })
    assert.deepEqual(corners, ['10px', '0px'])
})

test('a replaced dialog trigger opens and receives focus on close', async (t) => {
    const page = await openProbe(t, '?state=1')
    await page.locator('#replace-trigger').click()
    const trigger = page.locator('#trigger-1')
    await trigger.click()
    await page.locator('#state-dialog dialog[open]').waitFor()
    await page.locator('#change-open-trigger').click()
    const replacement = page.locator('#trigger-2')
    await replacement.waitFor({ state: 'attached' })
    assert.ok(await page.locator('#state-dialog dialog').evaluate((el) => el.matches(':modal')))
    await page.keyboard.press('Escape')
    await page.waitForFunction(() => document.activeElement.id === 'trigger-2')
    assert.equal(await replacement.getAttribute('aria-expanded'), 'false')
})

test('a media preview pauses playback and adopts its replacement trigger and source', async (t) => {
    const page = await openProbe(t, '?state=1')
    const preview = page.locator('#state-preview')
    const focused = page.locator(':focus')
    const listen = preview.getByRole('button', { name: 'Listen' })
    await listen.press('Space')
    const dialog = preview.getByRole('dialog')
    const player = dialog.locator('audio')
    await player.evaluate(async (audio) => {
        audio.loop = true
        await audio.play()
    })
    await dialog.getByRole('button', { name: 'Close' }).click()
    await dialog.waitFor({ state: 'hidden' })
    assert.ok(await preview.locator('audio').evaluate((audio) => audio.paused))
    // The native close event restores focus after the dialog becomes hidden.
    await listen.and(focused).waitFor()

    await listen.press('Enter')
    await dialog.waitFor()
    await preview.evaluate((el) =>
        window.Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).$set(
            'buttonVersion',
            1,
        ),
    )
    await dialog.locator('img').evaluate((image) => image.decode())
    assert.equal(await preview.locator('audio').count(), 0)
    await page.keyboard.press('Escape')
    await dialog.waitFor({ state: 'hidden' })
    const picture = preview.getByRole('button', { name: 'View picture', exact: true })
    await picture.and(focused).waitFor()
    assert.equal(await preview.getByRole('button').count(), 1)
    assert.equal(
        await picture.getAttribute('aria-controls'),
        await preview.locator('dialog').getAttribute('id'),
    )
    await picture.press('Enter')
    await dialog.locator('img').evaluate((image) => image.decode())
    await page.keyboard.press('Escape')
    await dialog.waitFor({ state: 'hidden' })
    await picture.and(focused).waitFor()
})

test('removing an open dialog panel releases its scroll lock', async (t) => {
    const page = await openProbe(t, '?state=1')
    await page.evaluate(() => (document.documentElement.style.overflow = 'auto'))
    await page.locator('#trigger-0').click()
    await page.locator('#state-dialog dialog[open]').waitFor()
    assert.equal(await page.evaluate(() => document.documentElement.style.overflow), 'hidden')
    await page.locator('#panel-0').click()
    await page.locator('#panel-1').waitFor()
    assert.ok(await page.locator('#state-dialog dialog').evaluate((el) => el.matches(':modal')))
    assert.equal(await page.evaluate(() => document.documentElement.style.overflow), 'hidden')
    await page.locator('#remove-panel').click()
    await page.locator('#state-dialog dialog').waitFor({ state: 'detached' })
    await page.waitForFunction(() => document.documentElement.style.overflow === 'auto')
    assert.equal(await page.locator('#trigger-0').getAttribute('aria-expanded'), 'false')
})

test('an uncontrolled slider keeps its edited handles and form values until remount', async (t) => {
    const page = await openProbe(t, '?state=1')
    const form = page.locator('#ranges')
    const values = () => form.evaluate((el) => new FormData(el).getAll('range[]'))
    await page.locator('#range').press('ArrowRight')
    assert.deepEqual(await values(), ['21', '80'])
    await page.locator('#defaults').click()
    await page.waitForFunction(() => Livewire.first().defaults.length === 1)
    await page.locator('#lock').click()
    await page.waitForFunction(() => document.getElementById('range').disabled)
    assert.equal(await page.locator('[data-slider-index]').count(), 2)
    assert.deepEqual(await values(), ['21', '80'])
    await form.evaluate((el) => el.reset())
    await page.waitForFunction(() => document.getElementById('range').value === '20')
    assert.deepEqual(await values(), ['20', '80'])
    await page.locator('#remount').click()
    await page.waitForFunction(() => document.querySelectorAll('[data-slider-index]').length === 1)
    assert.deepEqual(await values(), ['5'])
})

test('external form ownership includes custom inputs, readonly ratings and choices', async (t) => {
    const page = await openProbe(t, '?state=1')
    await page.waitForFunction(
        () =>
            document.querySelector('input[name="body"]:enabled') &&
            document.getElementById('external-editor')?.editor,
    )
    const form = page.locator('#external')
    const entries = await form.evaluate((el) => Object.fromEntries(new FormData(el)))
    assert.equal(entries.body, 'First')
    assert.equal(entries.signature, '[]')
    assert.equal(JSON.parse(entries.crop).width, 0.8)
    assert.equal(entries.score, '4')
    assert.equal(entries.team, '0')
    assert.equal(entries['members[]'], 'a')
    await page.locator('#lock').click()
    await page.waitForFunction(() => document.querySelector('[name="score"]').disabled)
    assert.deepEqual(await form.evaluate((el) => new FormData(el).getAll('score')), ['4'])
})

test('a readonly questionnaire submits the same answers on each question', async (t) => {
    const page = await openProbe(t, '?state=1')
    const answers = () =>
        page.locator('#readonly-survey').evaluate((form) => {
            const data = new FormData(form)
            return [data.getAll('single'), data.getAll('multi')]
        })
    const expected = [['a'], ['b', 'Other answer']]
    assert.deepEqual(await answers(), expected)
    await page.locator('#next-item').click()
    await page.getByText('Two', { exact: true }).waitFor()
    assert.deepEqual(await answers(), expected)
})

test('selecting an icon updates the bound parent and the form with its name', async (t) => {
    const page = await openProbe(t, '?state=1')
    await page.getByRole('button', { name: 'Choose an icon' }).click()
    const form = page.locator('#icon-form')
    const input = form.getByRole('combobox', { name: 'Icon', exact: true })
    await input.waitFor()
    await page.waitForLoadState('networkidle')
    const requests = []
    const trackUpdate = (request) => {
        if (request.method() === 'POST') requests.push(request)
    }
    page.on('request', trackUpdate)
    await input.click()
    const selected = form.getByRole('option', { name: 'circle-check', exact: true })
    await selected.waitFor()
    assert.ok((await form.getByRole('option').count()) <= 48)
    assert.equal(requests.length, 0)
    page.off('request', trackUpdate)

    await input.press('PageDown')
    await selected.waitFor({ state: 'hidden' })
    const highlighted = form.locator('[role="option"][data-highlighted]')
    await highlighted.waitFor()
    assert.equal(
        await input.getAttribute('aria-activedescendant'),
        await highlighted.getAttribute('id'),
    )
    await form.locator('[aria-keyshortcuts="PageUp"]').click()
    await selected.waitFor()
    await input.fill('ZOOM-OU')
    await form
        .locator('[role="option"][data-highlighted]')
        .filter({ hasText: 'zoom-out' })
        .waitFor()
    // Hold the response so the input, form and model have to update on the click itself.
    const hold = Promise.withResolvers()
    const update = (url) => url.pathname.endsWith('/update')
    let commits = 0
    await page.route(update, async (route) => {
        commits++
        await hold.promise
        await route.continue()
    })
    try {
        await input.press('Enter')
        assert.equal(await input.inputValue(), 'zoom-out')
        assert.equal(await form.evaluate((el) => new FormData(el).get('icon')), 'zoom-out')
        assert.equal(
            await form.evaluate(
                (el) =>
                    window.Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).icon,
            ),
            'zoom-out',
        )
    } finally {
        hold.resolve()
    }
    await page.waitForFunction(() => document.getElementById('chosen-icon').value === 'zoom-out')
    await page.waitForLoadState('networkidle')
    await page.unroute(update)
    assert.equal(commits, 1)
    assert.equal(await form.evaluate((el) => new FormData(el).get('icon')), 'zoom-out')

    await input.fill('zoom-in')
    await form.getByRole('option', { name: 'zoom-in', exact: true }).click()
    await page.waitForFunction(() => document.getElementById('chosen-icon').value === 'star')
    assert.equal(await input.inputValue(), 'star')
    assert.equal(await form.evaluate((el) => new FormData(el).get('icon')), 'star')

    await input.fill('ZOOM-OU')
    const retry = form.getByRole('option', { name: 'zoom-out', exact: true })
    await retry.waitFor()
    await page.route(update, (route) => route.abort(), { times: 1 })
    await retry.click()
    await page.waitForFunction(
        () => new FormData(document.getElementById('icon-form')).get('icon') === 'star',
    )
    assert.equal(await input.inputValue(), 'star')
})

test('side menus and their submenus stay inside a narrow viewport', async (t) => {
    const page = await openProbe(t, '?state=1', { viewport: { width: 360, height: 740 } })
    await page.locator('#side-trigger').click()
    const menus = page.locator('[role="menu"]:popover-open')
    const inside = (menu) =>
        menu.evaluate((el) => {
            const rect = el.getBoundingClientRect()
            return rect.left >= 0 && rect.right <= innerWidth
        })
    await menus.first().waitFor()
    assert.ok(await inside(menus.first()))
    await page.getByRole('button', { name: 'Options' }).press('ArrowRight')
    await menus.nth(1).waitFor()
    assert.ok(await inside(menus.nth(1)))
})

test('slot styles apply only to package elements, including inside caller slots', async (t) => {
    const page = await openProbe(t)
    const fixture = page.locator('#slot-isolation')
    const styles = (selector, property) =>
        fixture
            .locator(selector)
            .evaluateAll(
                (nodes, key) => nodes.map((node) => getComputedStyle(node).getPropertyValue(key)),
                property,
            )
    const separators = await styles('[data-slot="separator"], .ui-separator', 'height')
    assert.equal(separators.length, 4)
    assert.ok(parseFloat(separators[0]) > 1)
    assert.equal(separators[1], '1px')
    assert.ok(parseFloat(separators[2]) > 1)
    assert.equal(separators[3], '1px')
    assert.deepEqual(await styles('[data-slot="host-action"]', 'pointer-events'), ['auto'])
    assert.deepEqual(await styles('[data-slot="host-action"]', 'opacity'), ['1'])
    const [caller, component] = await styles(
        '[data-slot="bubble-content"], .ui-bubble-content',
        'background-color',
    )
    assert.equal(caller, 'rgba(0, 0, 0, 0)')
    assert.notEqual(component, caller)
})

test('combobox aligns the selected option to either trigger and handles an empty search', async (t) => {
    const page = await openProbe(t, '?alignment=1')
    for (const id of ['inline-search', 'popup-search', 'multi-search']) {
        const trigger = page.locator('#' + id)
        const root = page.locator('.ui-combobox-body').filter({ has: trigger })
        const panel = root.locator('[popover]')
        const anchor = root.locator('[data-ui-anchor-box]').first()
        await trigger.click()
        const selected = panel.getByRole('option', { selected: true }).last()
        await selected.waitFor()
        const row = await selected.boundingBox()
        const box = await anchor.boundingBox()
        assert.ok(Math.abs(row.y + row.height / 2 - box.y - box.height / 2) <= 1, id)

        const input = root.getByRole('combobox')
        await input.fill('no matching option')
        await panel.getByText('No matching results').waitFor()
        const empty = await panel.boundingBox()
        assert.ok(Math.abs(empty.y - box.y - box.height - 6) <= 1, id)
        await input.press('Escape')
        await panel.waitFor({ state: 'hidden' })
    }
})

test('touch opens the combobox below its trigger and keyboard reopening aligns the row', async (t) => {
    const page = await openProbe(t, '?alignment=1', { hasTouch: true })
    const trigger = page.locator('#popup-search')
    const panel = page.locator('.ui-combobox-body').filter({ has: trigger }).locator('[popover]')
    await trigger.tap()
    await panel.waitFor()
    const box = await trigger.boundingBox()
    const popup = await panel.boundingBox()
    assert.ok(Math.abs(popup.y - box.y - box.height - 6) <= 1)

    await page.keyboard.press('Escape')
    await panel.waitFor({ state: 'hidden' })
    await trigger.press('ArrowDown')
    const row = await panel.getByRole('option', { selected: true }).boundingBox()
    assert.ok(Math.abs(row.y + row.height / 2 - box.y - box.height / 2) <= 1)
})

test('a border variant keeps the same control and group dimensions', async (t) => {
    const page = await openProbe(t)
    const dimensions = (variant) =>
        page
            .locator(`#sizing-${variant}`)
            .locator('.ui-button, .ui-toggle-control, .ui-toggle-group, .ui-sidebar-menu-button')
            .evaluateAll((controls) =>
                controls.map((control) => {
                    const { width, height } = control.getBoundingClientRect()
                    return [width, height]
                }),
            )
    const defaults = await dimensions('default')
    assert.equal(defaults.length, 6)
    assert.deepEqual(await dimensions('outline'), defaults)
})

test('button fills and sidebar outlines paint up to the control edge', async (t) => {
    const page = await openProbe(t)
    for (const variant of ['default', 'outline']) {
        for (const selector of ['.ui-button', '.ui-sidebar-menu-button']) {
            const control = page.locator(`#sizing-${variant} ${selector}`)
            await control.scrollIntoViewIfNeeded()
            const { x, y, width } = await control.boundingBox()
            const pixel = (offset) =>
                page.screenshot({
                    clip: {
                        x: Math.floor(x + width / 2),
                        y: Math.round(y) + offset,
                        width: 1,
                        height: 1,
                    },
                })
            const outside = await pixel(-1)
            const edge = await pixel(0)
            const label = variant + ' ' + selector
            assert.deepEqual(outside, await pixel(-2), label + ' paints outside its bounds')
            assert.notDeepEqual(edge, outside, label + ' leaves the edge unpainted')
            if (variant === 'outline') {
                assert.notDeepEqual(edge, await pixel(2), label + ' has no visible border')
            }
        }
    }
})

for (const id of ['single-choice', 'multi-choice']) {
    test(`a new results page preserves the search and selection in ${id}`, async (t) => {
        const page = await openProbe(t)
        const input = page.locator('#' + id)
        await input.fill('bet')
        await page.waitForFunction(() => document.querySelector('#filtered').value === 'bet')
        const other = id === 'single-choice' ? 'multi-choice' : 'single-choice'
        await page.waitForFunction((id) => {
            const root = document.getElementById(id).closest('.ui-combobox-body')
            return root.querySelectorAll('[role="option"]').length === 1
        }, other)
        assert.equal(await input.inputValue(), 'bet')
        if (id === 'multi-choice') {
            const chip = page.locator('.ui-combobox-chip')
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

test('tightening the date limit between clicks starts a new submitted range', async (t) => {
    const page = await openProbe(t)
    await page.locator('#start-calendar').click()
    const calendar = page.locator('#booking')
    await calendar.getByRole('button', { name: 'Next month' }).click()
    await calendar.getByRole('button', { name: '2027-07-12' }).click()
    await page.locator('#booking-dates input[value="2027-07-12"]').waitFor({ state: 'attached' })
    await page.locator('#limit-september').click()
    for (const [index, date] of ['2027-09-15', '2027-09-18'].entries()) {
        await calendar.getByRole('button', { name: date }).click()
        await page.locator(`#booking-dates input[value="${date}"]`).waitFor({ state: 'attached' })
        const submitted = await page
            .locator('#booking-dates')
            .evaluate((form) => new FormData(form).getAll('period[]'))
        assert.deepEqual(submitted, index === 0 ? [date] : ['2027-09-15', date])
    }
})

test('native fields participate in labeling, validation, submission and form reset', async (t) => {
    const page = await openProbe(t)
    const number = page.getByLabel('Quantity', { exact: true })
    await page.locator('label[for="quantity"]').click()
    assert.equal(await number.evaluate((el) => el === document.activeElement), true)
    assert.equal(await number.getAttribute('placeholder'), 'Units')
    await number.fill('')
    assert.equal(await page.locator('#controls').evaluate((form) => form.checkValidity()), false)
    await number.fill('18')
    assert.equal(await page.locator('#controls').evaluate((form) => form.checkValidity()), true)

    const cells = page.locator('[data-otp-index]')
    assert.deepEqual(await cells.evaluateAll((items) => items.map((el) => el.getAttribute('id'))), [
        null,
        null,
    ])
    const form = page.locator('#verification')
    await cells.nth(1).fill('')
    assert.equal(await form.evaluate((el) => el.checkValidity()), false)
    await cells.first().fill('38')
    assert.equal(await form.evaluate((el) => new FormData(el).get('code')), '38')
    await cells.first().evaluate((el) => {
        const root = el.closest('.ui-input-otp')
        for (const input of root.querySelectorAll('input')) input.setAttribute('form', 'controls')
    })
    await form.evaluate(async (el) => {
        el.reset()
        await new Promise(requestAnimationFrame)
    })
    assert.equal(await cells.nth(1).inputValue(), '8')
    await page.locator('#controls').evaluate((el) => el.reset())
    await page.waitForFunction(
        () => new FormData(document.querySelector('#controls')).get('code') === '12',
    )
    assert.equal(await cells.nth(1).inputValue(), '2')
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

test('decimal typing preserves the native draft and submits the same Livewire value', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    for (const name of ['Plain amount', 'Bound amount']) {
        const input = page.getByRole('spinbutton', { name })
        await input.pressSequentially('1.05')
        await input.press('Tab')
        assert.equal(await input.inputValue(), '1.05', name)
        assert.equal(await input.evaluate((el) => new FormData(el.form).get(el.name)), '1.05')
    }
    const saved = page.locator('#saved-amount')
    await saved.filter({ hasText: /^1\.05$/ }).waitFor()
    await page.getByRole('button', { name: 'Set amount' }).click()
    await saved.filter({ hasText: /^2\.75$/ }).waitFor()
    assert.equal(await page.getByRole('spinbutton', { name: 'Bound amount' }).inputValue(), '2.75')
    await page.locator('#amounts').evaluate((form) => form.reset())
    await page.waitForFunction(() => document.querySelector('#saved-amount').textContent === '')
    assert.equal(await page.getByRole('spinbutton', { name: 'Plain amount' }).inputValue(), '')
    assert.equal(await page.getByRole('spinbutton', { name: 'Bound amount' }).inputValue(), '')
})

test('reset column visibility survives toggling a different column', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const grid = page.locator('#orders')
    await grid.getByRole('button', { name: 'Columns', exact: true }).click()
    const cost = grid.getByRole('checkbox', { name: 'Cost' })
    const costHeader = grid.getByRole('columnheader', { name: 'Cost' })
    await cost.uncheck()
    await costHeader.waitFor({ state: 'detached' })
    await grid.getByRole('button', { name: 'Reset layout' }).click()
    await costHeader.waitFor()
    assert.equal(await cost.isChecked(), true)
    await grid.getByRole('checkbox', { name: 'Region' }).uncheck()
    await grid.getByRole('columnheader', { name: 'Region' }).waitFor({ state: 'detached' })
    assert.equal(await costHeader.isVisible(), true)
})

test('column edits preserve the last visible field and merge before the next server render', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const grid = page.locator('#orders')
    const hidden = page.locator('#hidden-columns')
    await page.getByRole('button', { name: 'Load saved layout' }).click()
    await hidden.filter({ hasText: 'retired' }).waitFor()
    await grid.getByRole('button', { name: 'Columns', exact: true }).click()
    assert.equal(await grid.getByRole('checkbox', { name: 'Title' }).isDisabled(), true)
    await grid.getByRole('checkbox', { name: 'Cost' }).check()
    await hidden.filter({ hasText: /^region$/ }).waitFor()
    await grid.locator('fieldset').evaluate((settings) => {
        settings.querySelector('input[value="region"]').click()
        settings.querySelector('input[value="cost"]').click()
    })
    await hidden.filter({ hasText: /^cost$/ }).waitFor()
    assert.equal(await grid.getByRole('columnheader', { name: 'Title' }).isVisible(), true)
    assert.equal(await grid.getByRole('columnheader', { name: 'Region' }).isVisible(), true)
})

test('the page checkbox follows individual rows after selecting the entire page', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const grid = page.locator('#orders')
    const all = grid.getByRole('checkbox', { name: 'Select eligible rows on this page' })
    await all.check()
    await page.waitForFunction(() => document.querySelector('#selection').textContent === 'one,two')
    await grid.getByRole('checkbox', { name: 'Select Second', exact: true }).uncheck()
    await page.waitForFunction(() => document.querySelector('#selection').textContent === 'one')
    assert.equal(await all.evaluate((el) => el.indeterminate), true)
    await grid.getByRole('checkbox', { name: 'Select First', exact: true }).uncheck()
    await page.waitForFunction(() => document.querySelector('#selection').textContent === '')
    assert.deepEqual(await all.evaluate((el) => [el.checked, el.indeterminate]), [false, false])
})

test('removing an open inner popover releases Escape and its trigger can reopen it', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const outer = page.getByRole('button', { name: 'Open outer' })
    const inner = page.getByRole('button', { name: 'Open inner' })
    const outerPanel = page.getByRole('dialog', { name: 'Outer', exact: true })
    await outer.click()
    await inner.click()
    await page.getByRole('button', { name: 'Remove inner' }).click()
    await page.locator('[aria-label="Inner"]').waitFor({ state: 'detached' })
    assert.equal(await inner.getAttribute('aria-expanded'), 'false')
    await page.keyboard.press('Escape')
    await outerPanel.waitFor({ state: 'hidden' })
    await outer.click()
    await page.getByRole('button', { name: 'Restore inner' }).click()
    await page.locator('[aria-label="Inner"]').waitFor({ state: 'attached' })
    await inner.click()
    await page.getByRole('button', { name: 'Replace inner' }).click()
    await page.locator('[aria-label="Inner"]').getByText('Panel 1').waitFor({ state: 'attached' })
    assert.equal(await inner.getAttribute('aria-expanded'), 'false')
    await inner.click()
    await page.getByText('Panel 1', { exact: true }).waitFor()
    await page.keyboard.press('Escape')
    await page.getByRole('dialog', { name: 'Inner', exact: true }).waitFor({ state: 'hidden' })
    assert.equal(await outerPanel.isVisible(), true)
    await page.keyboard.press('Escape')
    await outerPanel.waitFor({ state: 'hidden' })
})

test('Tour and Carousel bind their step action to replacement slot buttons', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    await page.getByRole('button', { name: 'Start tour' }).click()
    await page.getByRole('button', { name: 'Replace tour button' }).click()
    await page.getByRole('button', { name: 'Tour next 1' }).click()
    await page.getByText('Second step', { exact: true }).waitFor()
    await page.getByRole('button', { name: 'Close tour' }).click()
    await page.getByRole('button', { name: 'Carousel next 1' }).click()
    const current = page.locator('#slides button[aria-current="page"]')
    await current.filter({ hasText: /^2$/ }).waitFor()
    await page.getByRole('button', { name: 'Replace carousel button' }).click()
    const next = page.getByRole('button', { name: 'Carousel next 2' })
    await next.click()
    await current.filter({ hasText: /^3$/ }).waitFor()
    assert.equal(await next.isDisabled(), true)
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

test('a controlled questionnaire waits for its parent to accept reset navigation', async (t) => {
    const page = await openProbe(t)
    await page.locator('#start-questions').click()
    const form = page.locator('#controlled-questions form')
    const title = form.locator('.ui-questionnaire-title')
    await title.waitFor({ timeout: 30000 })
    assert.equal((await title.textContent()).trim(), 'Second question')
    await form.evaluate((el) => el.reset())
    await page.waitForFunction(() => document.querySelector('#requested-item').value === 'first')
    assert.equal((await title.textContent()).trim(), 'Second question')
    await page.locator('#accept-item').click()
    await page.locator('#accept-item[aria-pressed="true"]').waitFor()
    await form.evaluate((el) => el.reset())
    await title.filter({ hasText: 'First question' }).waitFor()
})

test('Tab follows the controls inside a popover and closes it when focus leaves', async (t) => {
    const page = await openProbe(t)
    await page.locator('#outer-trigger').press('Enter')
    const focused = () => page.evaluate(() => document.activeElement.id)
    await page.waitForFunction(() => document.activeElement.id === 'inner-trigger')
    await page.keyboard.press('Tab')
    assert.equal(await focused(), 'second-action')
    assert.equal(await page.locator('#popovers :popover-open').count(), 1)
    await page.keyboard.press('Shift+Tab')
    assert.equal(await focused(), 'inner-trigger')
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    await page.waitForFunction(() => !document.querySelector('#popovers :popover-open'))
    assert.equal(await focused(), 'start-editing')
})

test('combobox chips and listbox options retain their own keyboard navigation', async (t) => {
    const page = await openProbe(t)
    const input = page.locator('#multi-choice')
    await input.press('ArrowLeft')
    assert.equal(
        await page.evaluate(() => document.activeElement.matches('.ui-combobox-chip')),
        true,
    )
    await page.keyboard.press('Delete')
    await page.waitForFunction(() => document.querySelector('#chosen').value === '')
    await page.waitForFunction(() => document.activeElement.id === 'multi-choice')
    await input.press('ArrowDown')
    await page.waitForFunction(
        () => !!document.querySelector('#multi-choice').getAttribute('aria-activedescendant'),
    )
    await input.press('Enter')
    await page.waitForFunction(() => document.querySelector('#chosen').value === 'a')
    await page.locator('#choice').press('ArrowDown')
    await page.waitForFunction(() => document.activeElement.role === 'option')
    await page.keyboard.press('Escape')
    assert.equal(await page.evaluate(() => document.activeElement.id), 'choice')
})

test('menu arrows skip disabled items and typeahead moves focus', async (t) => {
    const page = await openProbe(t)
    await page.locator('#menu-trigger').press('ArrowDown')
    const action = () => page.evaluate(() => document.activeElement.textContent.trim())
    assert.equal(await action(), 'Archive')
    await page.keyboard.press('ArrowDown')
    assert.equal(await action(), 'Rename')
    await page.keyboard.press('a')
    assert.equal(await action(), 'Archive')
    await page.keyboard.press('End')
    assert.equal(await action(), 'Rename')
    await page.keyboard.press('Escape')
    assert.equal(await page.evaluate(() => document.activeElement.id), 'menu-trigger')
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
        assert.equal(await page.locator('svg.ui-icon circle').first().getAttribute('r'), '10')
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
        for (const selector of [
            '#disabled-button:disabled',
            '#disabled-link[aria-disabled="true"]',
        ]) {
            assert.deepEqual(
                await page.locator(selector).evaluate((el) => {
                    const style = getComputedStyle(el)
                    return [style.pointerEvents, style.opacity]
                }),
                ['none', '0.5'],
            )
        }
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
        assert.deepEqual(assets.filter(([name]) => name.startsWith('editor.')).sort(), [
            ['editor.css', 200],
            ['editor.js', 200],
        ])
        assert.equal(await page.locator('link[href*="editor.css"]').count(), 1)
        assert.equal(await page.locator('script[src*="editor.js"]').count(), 1)
        assert.deepEqual(errors, [])
    } finally {
        await browser.close()
    }
})

test('an editor-only page loads its stylesheet and script once across Livewire navigation', async (t) => {
    const browser = await chromium.launch()
    t.after(() => browser.close())
    const page = await browser.newPage()
    page.setDefaultTimeout(7000)
    const assets = []
    const errors = []
    page.on('pageerror', (error) => errors.push(error.message))
    page.on('request', (request) => {
        const path = new URL(request.url()).pathname
        if (path.startsWith('/esoboy/ui/')) assets.push(path.split('/')[3])
    })
    const base = process.env.UI_TEST_URL ?? 'http://127.0.0.1:18744'
    await page.goto(new URL('/ui-package-test?editors=1', base).href)
    const second = page.locator('#document-two [contenteditable="true"]')
    assert.equal(await second.innerText(), 'Two')
    assert.deepEqual([...assets].sort(), ['editor.css', 'editor.js'])
    assert.equal(await page.locator('link[href*="editor.css"]').count(), 1)
    const documentStyles = await page.locator('#document-two .document').evaluate((editor) => {
        // Check the editor product's own typography with the application's CSS disabled.
        const hostStyle = document.querySelector('link[href*="/build/"][rel="stylesheet"]')
        hostStyle.disabled = true
        const sample = document.createElement('div')
        sample.className = editor.className
        const heading = sample.appendChild(document.createElement('h2'))
        editor.after(sample)
        const ratio =
            parseFloat(getComputedStyle(heading).fontSize) /
            parseFloat(getComputedStyle(editor).fontSize)
        const toolbar = getComputedStyle(
            editor.closest('esoboy-editor').querySelector('.toolbar'),
        ).display
        sample.remove()
        hostStyle.disabled = false
        return [ratio, toolbar]
    })
    assert.deepEqual(documentStyles, [1.75, 'grid'])
    assert.equal(await page.locator('script[src*="editor.js"]').count(), 1)
    await page.getByRole('link', { name: 'General components' }).click()
    // Alpine sets aria-expanded once Livewire has loaded the new page's head scripts.
    await page.locator('#choice[aria-expanded="false"]').click()
    await page.getByRole('option', { name: 'One', exact: true }).click()
    await page.getByRole('link', { name: 'Standalone editors' }).click()
    assert.equal(await second.innerText(), 'Two')
    assert.deepEqual(assets.sort(), ['editor.css', 'editor.js', 'ui.js'])
    assert.deepEqual(errors, [])
})

test('a scoped theme updates type, color, radius and elevation on composed components', async (t) => {
    const page = await openProbe(t, '?composition=1')
    await page.addStyleTag({
        content: `[data-ui-theme="account"] * { transition: none !important; }
        [data-ui-theme="account"] {
            --text-body: 21px;
            --color-primary: #234567;
            --color-foreground: #213243;
            --radius: 12px;
            --shadow-card: 0 2px 7px #123456;
            --shadow-popover: none;
        }`,
    })
    const style = (selector, property) =>
        page.locator(selector).evaluate((el, name) => getComputedStyle(el)[name], property)
    assert.equal(await style('#outside-theme', 'fontSize'), '14px')
    assert.equal(await style('#inside-theme', 'fontSize'), '21px')
    assert.equal(await style('#inside-theme', 'backgroundColor'), 'rgb(35, 69, 103)')
    assert.equal(await style('#inside-theme', 'borderTopLeftRadius'), '12px')
    assert.equal(await style('#theme-utilities', 'fontSize'), '16px')
    assert.equal(await style('#theme-utilities', 'borderTopLeftRadius'), '8px')
    assert.equal(await style('#theme-caption', 'fontSize'), '18px')
    assert.equal(await style('#theme-card .ui-card-title', 'fontSize'), '24px')
    assert.equal(await style('#theme-card', 'color'), 'rgb(33, 50, 67)')
    assert.match(await style('#theme-card', 'boxShadow'), /2px 7px/)
    assert.equal(await style('#sidebar-input', 'fontSize'), '21px')
    await page.locator('#toggle-sidebar').click()
    await page.waitForFunction(
        () => document.getElementById('probe-sidebar').dataset.state === 'collapsed',
    )
    await page.locator('#theme-open').click()
    assert.equal(await style('[data-test="theme-popup"]', 'boxShadow'), 'none')
    assert.equal(await style('[data-test="theme-popup"]', 'outlineWidth'), '1px')
    await page.keyboard.press('Escape')
    await page.locator('[data-ui-theme]').evaluate((el) => {
        el.style.setProperty('--text-body', '14px')
        el.style.setProperty('--radius-sm', '3px')
    })
    assert.equal(await style('#theme-caption', 'fontSize'), '12px')
    assert.equal(await style('#theme-utilities', 'borderTopLeftRadius'), '3px')
    assert.equal(await style('#inside-theme', 'borderTopLeftRadius'), '12px')
})

test('time segments focus the selected option and save a new minute', async (t) => {
    const page = await openProbe(t, '?composition=1')
    await page.locator('#probe-time').click()
    const minute = page.locator('.ui-time-segment[data-value="minute"]')
    await minute.press('ArrowDown')
    await page.waitForFunction(() => document.activeElement?.textContent.trim() === '15')
    await page.getByRole('option', { name: '20', exact: true }).click()
    await page.waitForFunction(
        () => document.querySelector('input[name="probe-time"]').value === '09:20',
    )
})

test('a trace and its nested disclosure toggle independently', async (t) => {
    const page = await openProbe(t, '?composition=1')
    const trace = page.locator('#trace-panel')
    const nested = page.locator('[data-test="nested-panel"]')
    assert.equal(await trace.isVisible(), false)
    await page.locator('#toggle-trace').click()
    await trace.waitFor()
    assert.equal(await nested.isVisible(), false)
    await page.locator('#toggle-nested').press('Enter')
    await nested.waitFor()
    await page.locator('#toggle-nested').press('Enter')
    await nested.waitFor({ state: 'hidden' })
    assert.equal(await trace.isVisible(), true)
    await page.locator('#toggle-trace').click()
    await trace.waitFor({ state: 'hidden' })
})

test('column settings persist through a live page jump and ordinary pagination keeps URL parameters', async (t) => {
    const page = await openProbe(t, '?composition=1')
    const grid = page.locator('#composition-grid')
    await grid.locator('.ui-table-columns-trigger').click()
    const handle = grid.locator('[data-key="cost"] [data-column-handle]')
    await handle.press('ArrowUp')
    await page.waitForFunction(
        () => document.querySelector('#composition-grid thead th').textContent.trim() === 'Cost',
    )
    await page.waitForFunction(
        () => document.activeElement?.closest('[data-key]')?.dataset.key === 'cost',
    )
    await grid.locator('[data-key="cost"] input').uncheck()
    await grid.getByRole('columnheader', { name: 'Cost' }).waitFor({ state: 'hidden' })
    await page.keyboard.press('Escape')
    const live = grid.locator('.ui-pagination-jump input')
    await live.fill('3')
    await live.press('Enter')
    await grid.getByRole('cell', { name: '3', exact: true }).waitFor()
    assert.equal(await grid.getByRole('columnheader', { name: 'Cost' }).count(), 0)
    const ordinary = page.locator('#ordinary-pages input')
    await ordinary.fill('9')
    await ordinary.press('Enter')
    assert.equal(await ordinary.evaluate((el) => el.validity.rangeOverflow), true)
    await ordinary.fill('4')
    await Promise.all([
        page.waitForURL((url) => url.searchParams.get('ordinary') === '4'),
        ordinary.press('Enter'),
    ])
    const url = new URL(page.url())
    assert.equal(url.searchParams.get('filter'), 'open')
    assert.equal(url.hash, '#results')
})

test('OTP ignores invalid paste and preserves explicit deletion', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const slot = page.locator('#code-form [data-otp-index="1"]')
    const current = () =>
        page.locator('#code-form').evaluate((form) => new FormData(form).get('code'))
    const paste = (text) =>
        slot.evaluate((input, text) => {
            const event = new ClipboardEvent('paste', {
                clipboardData: new DataTransfer(),
                bubbles: true,
                cancelable: true,
            })
            event.clipboardData.setData('text/plain', text)
            input.dispatchEvent(event)
        }, text)
    await paste('abc')
    assert.equal(await current(), '1234')
    await paste('')
    assert.equal(await current(), '1234')
    await paste('a8b9')
    assert.equal(await current(), '1894')
    await slot.focus()
    await slot.press('Backspace')
    assert.equal(await current(), '194')
})

test('calendar keyboard navigation skips disabled dates without losing its grid column', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const start = page.getByRole('button', { name: '2026-11-17', exact: true })
    for (const [key, day] of [
        ['ArrowRight', '2026-11-19'],
        ['ArrowLeft', '2026-11-16'],
        ['ArrowDown', '2026-12-01'],
        ['Home', '2026-11-16'],
        ['End', '2026-11-21'],
    ]) {
        await start.focus()
        await page.keyboard.press(key)
        assert.equal(await page.locator(':focus').getAttribute('aria-label'), day)
    }
})

test('a touch swipe moves the drawer handle with the finger', async (t) => {
    const page = await openProbe(t, '?interactions=1', { hasTouch: true })
    await page.getByRole('button', { name: 'Open swipe drawer' }).click()
    const panel = page.getByRole('dialog', { name: 'Swipe drawer' })
    await panel.waitFor()
    const offset = await panel.evaluate((panel) => {
        const handle = panel.querySelector('.ui-drawer-handle')
        const send = (type, y) => {
            const event = new Event(type, { bubbles: true, cancelable: true })
            const point = { identifier: 1, target: handle, clientX: 20, clientY: y }
            Object.defineProperty(event, 'touches', {
                value: type === 'touchcancel' ? [] : [point],
            })
            handle.dispatchEvent(event)
        }
        send('touchstart', 100)
        send('touchmove', 115)
        send('touchmove', 145)
        const result = panel.style.getPropertyValue('--drag-y')
        send('touchcancel', 145)
        return result
    })
    assert.equal(offset, '30px')
})

test('group edges, horizontal fields and progress spacing follow their rendered structure', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    await page.addStyleTag({ content: '#group-with-select * { transition: none !important }' })
    const styles = await page.evaluate(() => {
        const read = (s) => getComputedStyle(document.querySelector(s))
        const pair = (id) =>
            [...document.querySelectorAll('#' + id + ' .ui-button')]
                .filter((b) => !b.closest('[popover]'))
                .map((b) => [
                    getComputedStyle(b).borderTopLeftRadius,
                    getComputedStyle(b).borderTopRightRadius,
                ])
        const toggle = read('#single-toggle .ui-toggle-control')
        return {
            pair: pair('group-with-select'),
            ending: pair('group-ending-in-select'),
            toggle: [toggle.borderTopLeftRadius, toggle.borderTopRightRadius],
            wrap: read('#horizontal-description').textWrapStyle,
            gap: read('.themed-progress').gap,
        }
    })
    assert.equal(styles.ending[0][1], '0px')
    assert.equal(styles.ending[1][0], '0px')
    assert.deepEqual(styles.pair[1], ['0px', '0px'])
    assert.notEqual(styles.toggle[0], '0px')
    assert.equal(styles.toggle[0], styles.toggle[1])
    assert.equal(styles.wrap, 'balance')
    assert.equal(styles.gap, '24px')
    const edges = await page.locator('#group-with-select').evaluate((group) => {
        const first = group.querySelector('.ui-button'),
            middle = group.querySelector('.ui-select-control')
        group.dir = 'rtl'
        const rtl = [
            getComputedStyle(first).borderTopLeftRadius,
            getComputedStyle(middle).borderRightWidth,
        ]
        group.dataset.orientation = 'vertical'
        return [
            ...rtl,
            getComputedStyle(first).borderBottomLeftRadius,
            getComputedStyle(middle).borderTopWidth,
        ]
    })
    assert.deepEqual(edges, ['0px', '0px', '0px', '0px'])
})

test('checked toggles keep their selected fill and keyboard focus border', async (t) => {
    const page = await openProbe(t)
    await page.addStyleTag({
        content:
            '#sizing-outline { --color-muted: #246; --color-ring: #468 } .ui-toggle-control { transition: none !important }',
    })
    await page.keyboard.press('Tab')
    for (const selector of ['.ui-toggle', '.ui-toggle-group-item']) {
        const control = page.locator('#sizing-outline ' + selector).first()
        await control.locator('input').focus()
        await page.keyboard.press('Space')
        assert.deepEqual(
            await control.locator('span.ui-toggle-control').evaluate((el) => {
                const style = getComputedStyle(el)
                return [style.backgroundColor, style.borderTopColor]
            }),
            ['rgb(34, 68, 102)', 'rgb(68, 102, 136)'],
        )
    }
})

test('navigation and its nested popover each position once on viewport resize', async (t) => {
    const page = await openProbe(t, '?interactions=1')
    const outer = page.getByRole('button', { name: 'Browse sections' })
    await outer.focus()
    await outer.press('ArrowDown')
    const inner = page.getByRole('button', { name: 'Open section details' })
    await inner.click()
    await page.locator('#resizing-nav .ui-popover-content:popover-open').waitFor()
    const reads = await page.locator('#resizing-nav').evaluate((nav) => {
        const triggers = [
            nav.querySelector('[data-ui-navigation-value] button'),
            nav.querySelector('.ui-popover-trigger button'),
        ]
        const counts = [0, 0]
        triggers.forEach((el, i) => {
            el.getBoundingClientRect = () => {
                counts[i]++
                return Element.prototype.getBoundingClientRect.call(el)
            }
        })
        try {
            window.dispatchEvent(new Event('resize'))
            return counts
        } finally {
            triggers.forEach((el) => delete el.getBoundingClientRect)
        }
    })
    assert.deepEqual(reads, [1, 1])
})

test('sidebar buttons and links expose their tooltips only when collapsed on desktop', async (t) => {
    const page = await openProbe(t, '?composition=1')
    const sidebar = page.locator('#probe-sidebar')
    const states = () =>
        sidebar
            .locator('.ui-tooltip')
            .evaluateAll((items) => items.map((el) => el.dataset.disabled))
    assert.deepEqual(await states(), ['true', 'true'])
    await page.locator('#toggle-sidebar').click()
    await page.waitForFunction(
        () => document.querySelector('#probe-sidebar').dataset.state === 'collapsed',
    )
    assert.deepEqual(await states(), ['false', 'false'])
    await sidebar.getByRole('link', { name: 'Archive' }).focus()
    await sidebar.getByRole('tooltip', { name: 'Archived documents' }).waitFor()
    await page.setViewportSize({ width: 390, height: 800 })
    await page.waitForFunction(
        () => document.querySelector('#probe-sidebar').dataset.mobile === 'true',
    )
    assert.deepEqual(await states(), ['true', 'true'])
})

test('crop dragging previews the frame and commits its form value on release', async (t) => {
    const page = await openProbe(t, '?state=1')
    const surface = page.locator('[data-ui-crop-surface]')
    const frame = surface.locator('[data-ui-crop-frame]')
    const value = () =>
        page
            .locator('input[type="hidden"][name="crop"]')
            .evaluate((input) => JSON.parse(input.value))
    await surface.evaluate((el) => Object.assign(el.style, { width: '200px', height: '120px' }))
    await surface.scrollIntoViewIfNeeded()
    const before = await value(),
        box = await surface.boundingBox()
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2)
    await page.mouse.down()
    await page.mouse.move(box.x + box.width * 0.55, box.y + box.height / 2)
    const preview = await frame.evaluate((el) => parseFloat(el.style.left) / 100)
    assert.ok(preview > before.x)
    assert.deepEqual(await value(), before)
    await page.mouse.up()
    const committed = await value()
    assert.ok(Math.abs(committed.x - preview) < 0.001)
    assert.equal(committed.width, before.width)
})

test('the first editor undo returns to the loaded document and redo restores the edit', async (t) => {
    const page = await openProbe(t, '?editors=1')
    const root = page.locator('esoboy-editor').first()
    const body = root.getByRole('textbox', { name: 'Edit content' })
    const undo = root.getByRole('button', { name: 'Undo', exact: true })
    await body.waitFor()
    await page.waitForFunction(
        () => document.querySelector('esoboy-editor .document')?.textContent === 'One',
    )
    assert.equal(await undo.isDisabled(), true)
    await body.press('Control+End')
    await page.keyboard.insertText(' edit')
    await undo.click()
    assert.equal(await body.innerText(), 'One')
    await root.getByRole('button', { name: 'Redo', exact: true }).click()
    assert.equal(await body.innerText(), 'One edit')
})

test('a late-mounted bound editor loads the current draft without recording its Blade snapshot as an edit', async (t) => {
    const page = await openProbe(t, '?deferredEditor=1')
    await page.getByRole('button', { name: 'Mount draft' }).click()
    const editor = page.locator('#deferred-editor')
    await page.waitForFunction(
        () => document.querySelector('#deferred-editor .document')?.textContent === 'Updated draft',
    )
    assert.equal(await editor.getByRole('button', { name: 'Undo', exact: true }).isDisabled(), true)
    await page.locator('#deferred-draft').evaluate((form) => form.reset())
    await page.waitForFunction(
        () => document.querySelector('#deferred-editor .document')?.textContent === 'First',
    )
})
