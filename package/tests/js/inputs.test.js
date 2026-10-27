import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import { defineInputs } from '../../resources/js/inputs.js'

beforeAll(() => defineInputs())

afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
})

describe('tags input', () => {
    function mount(attributes = `data-value='["php"]'`) {
        document.body.innerHTML = `
            <ui-tags-input ${attributes}>
                <template data-tags-input-template><span data-slot="tags-input-tag"><span data-tags-input-label></span><button data-tags-input-remove></button></span></template>
                <span data-slot="tags-input-tag"><span data-tags-input-label>php</span></span>
                <input data-slot="tags-input-input">
            </ui-tags-input>`

        return document.querySelector('ui-tags-input')
    }

    const labels = element => [...element.querySelectorAll('[data-tags-input-label]')].map(label => label.textContent)
    const key = (element, name) => element.input.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }))
    const type = (element, text) => {
        element.input.value = text
        element.input.dispatchEvent(new Event('input', { bubbles: true }))
    }

    it('renders removable chips from the value', () => {
        const element = mount()

        expect(labels(element)).toEqual(['php'])
        expect(element.querySelectorAll('[data-tags-input-remove]')).toHaveLength(1)
    })

    it('adds on Enter and on commas, without duplicates, announcing from itself', () => {
        const element = mount()
        const seen = []
        element.addEventListener('change', event => event.target === element && seen.push(element.value))

        element.input.value = 'vue'
        key(element, 'Enter')
        type(element, 'php, go，rust')

        expect(element.value).toEqual(['php', 'vue', 'go', 'rust'])
        expect(element.input.value).toBe('')
        expect(seen).toEqual([['php', 'vue'], ['php', 'vue', 'go', 'rust']])
    })

    it('removes with the chip button and Backspace', () => {
        const element = mount(`data-value='["php","vue","go"]'`)

        element.querySelector('[data-tags-input-remove]').click()
        expect(element.value).toEqual(['vue', 'go'])

        key(element, 'Backspace')
        expect(element.value).toEqual(['vue'])
    })

    it('stops at the maximum', () => {
        const element = mount(`data-value='["php"]' data-max="2"`)

        type(element, 'a,b,c')

        expect(element.value).toEqual(['php', 'a'])
        expect(element.input.disabled).toBe(true)
    })

    it('keeps working when a morph replaces its input', () => {
        const element = mount()

        element.input.replaceWith(Object.assign(document.createElement('input'), { value: 'go' }))
        element.querySelector('input').dataset.slot = 'tags-input-input'
        key(element, 'Enter')

        expect(element.value).toEqual(['php', 'go'])
    })

    it('writes hidden inputs for form posts', () => {
        const element = mount(`data-value='["php"]' data-name="tags"`)

        element.value = ['a', 'b']

        expect([...element.querySelectorAll('input[type="hidden"]')].map(input => `${input.name}=${input.value}`)).toEqual(['tags[]=a', 'tags[]=b'])
    })
})

describe('number input', () => {
    function mount(attributes = 'data-min="0" data-max="3" data-step="1"', value = '1') {
        document.body.innerHTML = `
            <ui-number-input ${attributes}>
                <button data-number-input="decrement"></button>
                <input data-slot="input-group-control" value="${value}">
                <button data-number-input="increment"></button>
            </ui-number-input>`

        return document.querySelector('ui-number-input')
    }

    const press = (element, direction) => {
        const button = element.querySelector(`[data-number-input="${direction}"]`)

        button.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, button: 0 }))
        button.dispatchEvent(new MouseEvent('pointerup', { bubbles: true }))
    }

    it('steps within its bounds and disables the spent button', () => {
        const element = mount()
        const seen = []
        element.addEventListener('change', event => event.target === element && seen.push(element.value))

        press(element, 'increment')
        press(element, 'increment')
        press(element, 'increment')

        expect(element.value).toBe(3)
        expect(element.input.value).toBe('3')
        expect(element.querySelector('[data-number-input="increment"]').disabled).toBe(true)
        expect(seen).toEqual([2, 3])
    })

    it('repeats while a button is held', () => {
        vi.useFakeTimers()
        const element = mount('data-step="1"', '0')
        const button = element.querySelector('[data-number-input="increment"]')

        button.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, button: 0 }))
        vi.advanceTimersByTime(400 + 50 * 3)
        button.dispatchEvent(new MouseEvent('pointerup', { bubbles: true }))
        vi.advanceTimersByTime(500)

        expect(element.value).toBe(5)
    })

    it('keeps the step precision and steps with the arrows', () => {
        const element = mount('data-step="0.1"', '0.2')

        element.input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowUp', bubbles: true, cancelable: true }))

        expect(element.value).toBe(0.3)
    })

    it('reads typed text on change, clamped, and empties to null', () => {
        const element = mount()

        element.input.value = '9'
        element.input.dispatchEvent(new Event('change', { bubbles: true }))
        expect(element.value).toBe(3)

        element.input.value = ''
        element.input.dispatchEvent(new Event('change', { bubbles: true }))
        expect(element.value).toBeNull()
    })
})
