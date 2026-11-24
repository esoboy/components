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
    await page.waitForFunction(() => window.Livewire?.all().length > 0)
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
        canvas.closest('.ui-signature').style.setProperty('--ui-ink', '#453682')
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
