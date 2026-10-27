import { afterEach, beforeAll, expect, it } from 'vitest'
import { register as registerCascader } from '../../resources/js/components/cascader.js'

beforeAll(() => registerCascader())

afterEach(() => {
    document.body.innerHTML = ''
})

const options = [
    { value: 'zj', label: 'Zhejiang', children: [
        { value: 'hz', label: 'Hangzhou', children: [{ value: 'xh', label: 'Xihu' }, { value: 'bj', label: 'Binjiang' }] },
        { value: 'nb', label: 'Ningbo' },
    ] },
    { value: 'bj', label: 'Beijing', disabled: true },
]

function mount(attributes = '') {
    document.body.innerHTML = `
        <eso-cascader data-separator="/" data-placeholder="Pick" ${attributes}>
            <script type="application/json" data-cascader-options>${JSON.stringify(options)}</script>
            <button data-cascader-trigger><span data-slot="select-value">Pick</span></button>
            <div data-slot="cascader-content" popover="auto"></div>
        </eso-cascader>`

    return document.querySelector('eso-cascader')
}

const columns = cascader => [...cascader.content.querySelectorAll('[data-slot="cascader-column"]')].map(column => [...column.querySelectorAll('[data-cascader-item]')].map(item => item.textContent))
const item = (cascader, label) => [...cascader.content.querySelectorAll('[data-cascader-item]')].find(element => element.textContent === label)
const announced = cascader => {
    const seen = []
    cascader.addEventListener('change', event => event.target === cascader && seen.push(cascader.value))
    return seen
}

it('opens on the current path, a column per level', () => {
    const cascader = mount(`data-value='["zj","hz","xh"]'`)

    cascader.open()

    expect(columns(cascader)).toEqual([['Zhejiang', 'Beijing'], ['Hangzhou', 'Ningbo'], ['Xihu', 'Binjiang']])
    expect(item(cascader, 'Xihu').getAttribute('aria-selected')).toBe('true')
    expect(item(cascader, 'Zhejiang').hasAttribute('data-expanded')).toBe(true)
    expect(document.activeElement).toBe(item(cascader, 'Xihu'))
})

it('opens parents and chooses a leaf, closing', () => {
    const cascader = mount()
    const seen = announced(cascader)

    cascader.open()
    item(cascader, 'Zhejiang').click()
    expect(columns(cascader)).toHaveLength(2)
    expect(seen).toEqual([])

    item(cascader, 'Ningbo').click()

    expect(cascader.value).toEqual(['zj', 'nb'])
    expect(seen).toEqual([['zj', 'nb']])
    expect(cascader.isOpen).toBe(false)
    expect(cascader.trigger.textContent).toBe('Zhejiang / Ningbo')
})

it('chooses any level with change-on-select', () => {
    const cascader = mount('data-change-on-select')

    cascader.open()
    item(cascader, 'Zhejiang').click()

    expect(cascader.value).toEqual(['zj'])
    expect(cascader.isOpen).toBe(true)
})

it('skips disabled options', () => {
    const cascader = mount()

    cascader.open()
    item(cascader, 'Beijing').click()

    expect(cascader.value).toEqual([])
})

it('moves through levels with the keyboard', () => {
    const cascader = mount()
    const key = name => document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }))

    cascader.open()
    key('ArrowRight')
    expect(document.activeElement.textContent).toBe('Hangzhou')

    key('ArrowRight')
    expect(document.activeElement.textContent).toBe('Xihu')

    key('ArrowLeft')
    expect(document.activeElement.textContent).toBe('Hangzhou')

    key('ArrowDown')
    key('Enter')
    expect(cascader.value).toEqual(['zj', 'nb'])
})

it('takes values set by Livewire and writes hidden inputs', () => {
    const cascader = mount('data-name="region"')

    cascader.value = ['zj', 'hz']

    expect(cascader.trigger.textContent).toBe('Zhejiang / Hangzhou')
    expect([...cascader.querySelectorAll('input[type="hidden"]')].map(input => `${input.name}=${input.value}`)).toEqual(['region[]=zj', 'region[]=hz'])

    cascader.value = null
    expect(cascader.trigger.textContent).toBe('Pick')
    expect(cascader.trigger.hasAttribute('data-placeholder')).toBe(true)
})
