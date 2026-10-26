import { afterEach, beforeAll, describe, expect, it } from 'vitest'
import { defineCombobox } from '../../resources/js/combobox.js'

beforeAll(() => defineCombobox())

afterEach(() => {
    document.body.innerHTML = ''
})

const items = `
    <div data-slot="combobox-item" data-value="next">Next.js<span data-slot="combobox-item-indicator"></span></div>
    <div data-slot="combobox-item" data-value="svelte">SvelteKit<span data-slot="combobox-item-indicator"></span></div>
    <div data-slot="combobox-item" data-value="remix" aria-disabled="true">Remix<span data-slot="combobox-item-indicator"></span></div>
    <div data-slot="combobox-item" data-value="astro">Astro<span data-slot="combobox-item-indicator"></span></div>`

function single(attributes = '') {
    document.body.innerHTML = `
        <ui-combobox ${attributes}>
            <div data-slot="input-group">
                <input data-combobox-input>
                <button data-combobox-trigger></button>
                <button data-combobox-clear></button>
            </div>
            <div data-slot="combobox-content" popover="manual">
                <div data-slot="combobox-empty">None</div>
                <div data-slot="combobox-list">${items}</div>
            </div>
        </ui-combobox>`

    return document.querySelector('ui-combobox')
}

function multiple(attributes = '') {
    document.body.innerHTML = `
        <ui-combobox data-multiple ${attributes}>
            <div data-slot="combobox-chips">
                <template data-combobox-chip><div data-slot="combobox-chip"><span data-combobox-chip-label></span><button data-combobox-chip-remove></button></div></template>
                <input data-combobox-input>
            </div>
            <div data-slot="combobox-content" popover="manual"><div data-slot="combobox-list">${items}</div></div>
        </ui-combobox>`

    return document.querySelector('ui-combobox')
}

const type = (combobox, text) => {
    combobox.input.value = text
    combobox.input.dispatchEvent(new Event('input', { bubbles: true }))
}
const key = (combobox, name) => combobox.input.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }))
const visible = combobox => combobox.items.filter(item => ! item.hidden).map(item => item.dataset.value)
const highlighted = combobox => combobox.querySelector('[data-highlighted="true"]')?.dataset.value
const announced = combobox => {
    const seen = []
    combobox.addEventListener('input', event => event.target === combobox && seen.push(JSON.stringify(combobox.value)))
    return seen
}

describe('single', () => {
    it('shows the chosen item in the input', () => {
        const combobox = single('data-value=\'"svelte"\'')

        expect(combobox.value).toBe('svelte')
        expect(combobox.input.value).toBe('SvelteKit')
    })

    it('filters as you type, without highlighting, and shows the empty state', () => {
        const combobox = single()

        type(combobox, 'as')
        expect(combobox.isOpen).toBe(true)
        expect(visible(combobox)).toEqual(['astro'])
        expect(highlighted(combobox)).toBeUndefined()

        type(combobox, 'zzz')
        expect(combobox.content.hasAttribute('data-empty')).toBe(true)
    })

    it('chooses with the arrows and Enter, then closes', () => {
        const combobox = single()
        const seen = announced(combobox)

        combobox.input.click()
        key(combobox, 'ArrowDown')
        key(combobox, 'ArrowDown')
        expect(highlighted(combobox)).toBe('svelte')

        key(combobox, 'ArrowDown')
        expect(highlighted(combobox)).toBe('astro')

        key(combobox, 'Enter')
        expect(combobox.value).toBe('astro')
        expect(combobox.input.value).toBe('Astro')
        expect(combobox.isOpen).toBe(false)
        expect(seen).toEqual(['"astro"'])
    })

    it('restores the chosen text after an unfinished search', () => {
        const combobox = single('data-value=\'"next"\'')

        type(combobox, 'sv')
        key(combobox, 'Escape')

        expect(combobox.input.value).toBe('Next.js')
    })

    it('clears', () => {
        const combobox = single('data-value=\'"next"\'')
        const clear = combobox.querySelector('[data-combobox-clear]')

        expect(clear.hidden).toBe(false)
        clear.click()

        expect(combobox.value).toBeNull()
        expect(combobox.input.value).toBe('')
        expect(clear.hidden).toBe(true)
    })

    it('toggles from the trigger and closes on an outside press', () => {
        const combobox = single()

        combobox.querySelector('[data-combobox-trigger]').click()
        expect(combobox.isOpen).toBe(true)

        document.body.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true }))
        expect(combobox.isOpen).toBe(false)
    })

    it('takes values set by Livewire', () => {
        const combobox = single()

        combobox.value = 'next'

        expect(combobox.input.value).toBe('Next.js')
        expect(combobox.querySelector('[data-value="next"]').getAttribute('aria-selected')).toBe('true')
    })
})

describe('multiple', () => {
    it('renders chips for the chosen items', () => {
        const combobox = multiple('data-value=\'["next","astro"]\'')

        expect(combobox.value).toEqual(['next', 'astro'])
        expect([...combobox.querySelectorAll('[data-slot="combobox-chip"]')].map(chip => chip.textContent)).toEqual(['Next.js', 'Astro'])
    })

    it('toggles items and stays open', () => {
        const combobox = multiple()
        const seen = announced(combobox)

        combobox.input.click()
        combobox.querySelector('[data-slot="combobox-item"][data-value="svelte"]').click()
        combobox.querySelector('[data-slot="combobox-item"][data-value="next"]').click()
        combobox.querySelector('[data-slot="combobox-item"][data-value="svelte"]').click()

        expect(combobox.value).toEqual(['next'])
        expect(combobox.isOpen).toBe(true)
        expect(seen).toEqual(['["svelte"]', '["svelte","next"]', '["next"]'])
    })

    it('removes chips with their button or Backspace', () => {
        const combobox = multiple('data-value=\'["next","svelte","astro"]\'')

        combobox.querySelector('[data-combobox-chip-remove]').click()
        expect(combobox.value).toEqual(['svelte', 'astro'])

        key(combobox, 'Backspace')
        expect(combobox.value).toEqual(['svelte'])
    })

    it('writes hidden inputs for form posts', () => {
        const combobox = multiple('name="tags" data-value=\'["next","astro"]\'')

        expect([...combobox.querySelectorAll(':scope > input[type="hidden"]')].map(input => `${input.name}=${input.value}`)).toEqual(['tags[]=next', 'tags[]=astro'])
    })
})
