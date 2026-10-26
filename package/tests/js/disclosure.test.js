import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import { defineAccordion } from '../../resources/js/accordion.js'
import { defineCollapsible } from '../../resources/js/collapsible.js'

beforeAll(() => {
    defineAccordion()
    defineCollapsible()
})

afterEach(() => {
    document.body.innerHTML = ''
})

describe('accordion', () => {
    function mount(attributes = '') {
        document.body.innerHTML = `
            <ui-accordion ${attributes}>
                <details data-slot="accordion-item"><summary data-slot="accordion-trigger">A</summary>a</details>
                <details data-slot="accordion-item"><summary data-slot="accordion-trigger" aria-disabled="true">B</summary>b</details>
                <details data-slot="accordion-item"><summary data-slot="accordion-trigger">C</summary>c</details>
            </ui-accordion>`

        return document.querySelector('ui-accordion')
    }

    const press = key => document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }))

    it('names its items so a single one stays open', () => {
        const accordion = mount()
        const names = accordion.items.map(item => item.getAttribute('name'))

        expect(names[0]).toMatch(/^ui-accordion-\d+$/)
        expect(new Set(names).size).toBe(1)
    })

    it('leaves items unnamed when multiple', () => {
        expect(mount('data-multiple').items.map(item => item.getAttribute('name'))).toEqual([null, null, null])
    })

    it('names items added by a morph', async () => {
        const accordion = mount()

        accordion.insertAdjacentHTML('beforeend', '<details data-slot="accordion-item"><summary>D</summary></details>')

        await vi.waitFor(() => expect(accordion.items[3].getAttribute('name')).toBe(accordion.items[0].getAttribute('name')))
    })

    it('moves between enabled triggers with the arrows', () => {
        const accordion = mount()
        const [a, , c] = accordion.querySelectorAll('summary')

        a.focus()
        press('ArrowDown')
        expect(document.activeElement).toBe(c)

        press('ArrowDown')
        expect(document.activeElement).toBe(a)

        press('End')
        expect(document.activeElement).toBe(c)
    })

    it('does not toggle disabled items', () => {
        const accordion = mount()
        const event = new MouseEvent('click', { bubbles: true, cancelable: true })

        accordion.querySelectorAll('summary')[1].dispatchEvent(event)

        expect(event.defaultPrevented).toBe(true)
    })
})

describe('collapsible', () => {
    function mount(attributes = '') {
        document.body.innerHTML = `
            <ui-collapsible ${attributes}>
                <button data-slot="collapsible-trigger">Toggle</button>
                <button data-collapsible-trigger>Also</button>
                <div data-slot="collapsible-content" hidden>Details</div>
            </ui-collapsible>`

        return document.querySelector('ui-collapsible')
    }

    const content = () => document.querySelector('[data-slot="collapsible-content"]')

    it('toggles from its trigger or any marked element, announcing from itself', () => {
        const collapsible = mount()
        const seen = []

        collapsible.addEventListener('input', event => event.target === collapsible && seen.push(collapsible.value))
        collapsible.querySelector('[data-slot="collapsible-trigger"]').click()

        expect(content().hidden).toBe(false)
        expect(collapsible.hasAttribute('data-open')).toBe(true)

        collapsible.querySelector('[data-collapsible-trigger]').click()

        expect(content().hidden).toBe(true)
        expect(seen).toEqual([true, false])
    })

    it('links triggers to the content', () => {
        const collapsible = mount('data-open')

        for (const trigger of collapsible.querySelectorAll('button')) {
            expect(trigger.getAttribute('aria-expanded')).toBe('true')
            expect(trigger.getAttribute('aria-controls')).toBe(content().id)
            expect(trigger.hasAttribute('data-panel-open')).toBe(true)
        }
    })

    it('takes values set by Livewire and restores them after a morph', async () => {
        const collapsible = mount()

        collapsible.value = true
        expect(content().hidden).toBe(false)

        content().hidden = true
        await vi.waitFor(() => expect(content().hidden).toBe(false))
    })

    it('leaves a nested collapsible alone', () => {
        const outer = mount()

        content().innerHTML = '<ui-collapsible><button data-slot="collapsible-trigger">Inner</button></ui-collapsible>'
        content().querySelector('button').click()

        expect(outer.value).toBe(false)
    })
})
