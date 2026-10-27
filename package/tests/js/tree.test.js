import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import { register as registerTree } from '../../resources/js/components/tree.js'

beforeAll(() => registerTree())

afterEach(() => {
    document.body.innerHTML = ''
})

const item = (value, label, children = '', attributes = '') => `
    <li role="treeitem" tabindex="-1" data-slot="tree-item" data-value="${value}" ${children ? 'aria-expanded="false"' : ''} ${attributes}>
        <div data-slot="tree-item-row"><span data-slot="tree-item-toggle"></span><input type="checkbox" data-tree-check><span data-slot="tree-item-label">${label}</span></div>
        ${children ? `<ul role="group" data-slot="tree-group" hidden>${children}</ul>` : ''}
    </li>`

const permissions = item('orders', 'Orders', item('view', 'View') + item('refund', 'Refund') + item('export', 'Export', '', 'aria-disabled="true"')) + item('users', 'Users', item('ban', 'Ban')) + item('settings', 'Settings')

function mount(attributes = '') {
    document.body.innerHTML = `<eso-tree ${attributes}>${permissions}</eso-tree>`

    return document.querySelector('eso-tree')
}

const node = (tree, value) => tree.querySelector(`[data-value="${value}"]`)
const row = (tree, value) => node(tree, value).querySelector(':scope > [data-slot="tree-item-row"]')
const announced = tree => {
    const seen = []
    tree.addEventListener('change', event => event.target === tree && seen.push(tree.value))
    return seen
}

describe('checkable', () => {
    it('cascades a checked parent down on load, and settles parents', () => {
        const tree = mount(`data-checkable data-value='["users","view"]'`)

        expect(tree.value).toEqual(['users', 'ban', 'view'].sort((a, b) => ['orders', 'view', 'refund', 'export', 'users', 'ban', 'settings'].indexOf(a) - ['orders', 'view', 'refund', 'export', 'users', 'ban', 'settings'].indexOf(b)))
        expect(node(tree, 'orders').getAttribute('aria-checked')).toBe('mixed')
        expect(node(tree, 'orders').querySelector('[data-tree-check]').indeterminate).toBe(true)
        expect(node(tree, 'users').getAttribute('aria-checked')).toBe('true')
    })

    it('checks a parent and every enabled child, announcing from the tree', () => {
        const tree = mount('data-checkable')
        const seen = announced(tree)

        row(tree, 'orders').click()

        expect(tree.value).toEqual(['orders', 'view', 'refund'])
        expect(seen).toEqual([['orders', 'view', 'refund']])

        row(tree, 'refund').click()
        expect(tree.value).toEqual(['view'])
        expect(node(tree, 'orders').getAttribute('aria-checked')).toBe('mixed')
    })

    it('ignores disabled items', () => {
        const tree = mount('data-checkable')

        row(tree, 'export').click()

        expect(tree.value).toEqual([])
    })
})

describe('single', () => {
    it('chooses an item and opens a parent from its row', () => {
        const tree = mount()

        row(tree, 'orders').click()

        expect(tree.value).toBe('orders')
        expect(node(tree, 'orders').getAttribute('aria-expanded')).toBe('true')
        expect(node(tree, 'orders').querySelector('[data-slot="tree-group"]').hidden).toBe(false)
        expect(node(tree, 'orders').getAttribute('aria-selected')).toBe('true')
    })

    it('opens and closes with the toggle alone', () => {
        const tree = mount()

        row(tree, 'users').querySelector('[data-slot="tree-item-toggle"]').click()

        expect(node(tree, 'users').getAttribute('aria-expanded')).toBe('true')
        expect(tree.value).toBeNull()
    })

    it('follows the ARIA tree keys', () => {
        const tree = mount()
        const key = name => document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }))

        node(tree, 'orders').focus()
        key('ArrowRight')
        expect(node(tree, 'orders').getAttribute('aria-expanded')).toBe('true')

        key('ArrowRight')
        expect(document.activeElement).toBe(node(tree, 'view'))

        key('ArrowDown')
        expect(document.activeElement).toBe(node(tree, 'refund'))

        key('ArrowLeft')
        expect(document.activeElement).toBe(node(tree, 'orders'))

        key('End')
        expect(document.activeElement).toBe(node(tree, 'settings'))

        key('Enter')
        expect(tree.value).toBe('settings')
        expect(node(tree, 'view').getAttribute('aria-level')).toBe('2')
    })

    it('keeps its open items through a morph', async () => {
        const tree = mount()

        tree.expand(node(tree, 'orders'), true)
        node(tree, 'orders').setAttribute('aria-expanded', 'false')

        await vi.waitFor(() => expect(node(tree, 'orders').getAttribute('aria-expanded')).toBe('true'))
    })
})

describe('tree select', () => {
    function picker(checkable = false) {
        document.body.innerHTML = `
            <eso-tree-select data-placeholder="Pick" ${checkable ? 'data-checkable' : ''}>
                <button data-tree-select-trigger><span data-slot="select-value">Pick</span></button>
                <div data-slot="tree-select-content" popover="auto"><eso-tree ${checkable ? 'data-checkable' : ''}>${permissions}</eso-tree></div>
            </eso-tree-select>`

        return document.querySelector('eso-tree-select')
    }

    it('closes after one choice and shows its label', () => {
        const element = picker()
        const seen = []
        element.addEventListener('change', event => event.target === element && seen.push(element.value))

        element.trigger.click()
        row(element.tree, 'settings').click()

        expect(element.content.matches(':popover-open')).toBe(false)
        expect(element.trigger.textContent).toBe('Settings')
        expect(seen).toEqual(['settings'])
    })

    it('stays open for checkable choices and lists their labels', () => {
        const element = picker(true)

        element.trigger.click()
        row(element.tree, 'users').click()

        expect(element.content.matches(':popover-open')).toBe(true)
        expect(element.trigger.textContent).toBe('Users, Ban')
    })
})
