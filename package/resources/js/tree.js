/*
 * <ui-tree> and <ui-tree-select>
 *
 * A tree chooses one item (.value is its value), or, when checkable, checks items with
 * checkboxes that cascade: checking a parent checks everything below it, and a parent
 * is checked when all its children are, mixed when some are (.value is every checked
 * value). input/change are dispatched from the tree, so wire:model binds on it.
 * Keyboard as in the ARIA tree pattern: Up/Down, Right opens or goes in, Left closes
 * or goes out, Home/End, Enter or Space chooses or checks.
 *
 * A Tree Select is a Select-style trigger opening a tree; it proxies the tree's value.
 */

const ITEM = '[data-slot="tree-item"]'

const own = (root, selector) => [...root.querySelectorAll(selector)].filter(element => element.closest('ui-tree') === root)
const childItems = item => [...(item.querySelector(':scope > [data-slot="tree-group"]')?.children ?? [])].filter(child => child.matches(ITEM))
const isParent = item => item.hasAttribute('aria-expanded')
const isDisabled = item => item.getAttribute('aria-disabled') === 'true' || Boolean(item.parentElement?.closest(`${ITEM}[aria-disabled="true"]`))

export class TreeElement extends HTMLElement {
    #value = null
    #expanded = new Set()
    #observer = new MutationObserver(() => this.#sync())

    connectedCallback() {
        try {
            this.#value = JSON.parse(this.dataset.value ?? 'null')
        } catch {
            this.#value = null
        }

        if (this.checkable) this.#value = this.#cascade(Array.isArray(this.#value) ? this.#value : [])

        for (const item of this.items) if (item.getAttribute('aria-expanded') === 'true') this.#expanded.add(item.dataset.value)

        this.addEventListener('click', event => this.#onClick(event))
        this.addEventListener('keydown', event => this.#onKey(event))

        this.#sync()

        // Livewire morphs put back the server's open and checked states: restore ours.
        this.#observer.observe(this, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-expanded', 'aria-selected', 'aria-checked', 'hidden'] })
    }

    disconnectedCallback() {
        this.#observer.disconnect()
    }

    get checkable() {
        return this.hasAttribute('data-checkable')
    }

    get items() {
        return own(this, ITEM)
    }

    get value() {
        return this.checkable ? [...this.#value] : this.#value
    }

    set value(value) {
        this.#value = this.checkable
            ? this.#cascade(Array.isArray(value) ? value : [])
            : (value === null || value === undefined || value === '' ? null : String(value))
        this.#sync()
    }

    /** Labels of the chosen or checked items, in tree order. */
    get labels() {
        return this.items.filter(item => item.getAttribute(this.checkable ? 'aria-checked' : 'aria-selected') === 'true').map(item => item.querySelector(':scope > [data-slot="tree-item-row"] [data-slot="tree-item-label"]')?.textContent ?? '')
    }

    expand(item, open = ! this.#expanded.has(item.dataset.value)) {
        if (! isParent(item)) return

        open ? this.#expanded.add(item.dataset.value) : this.#expanded.delete(item.dataset.value)
        this.#sync()
    }

    #announce() {
        this.dispatchEvent(new Event('input', { bubbles: true }))
        this.dispatchEvent(new Event('change', { bubbles: true }))
    }

    #choose(item) {
        if (isDisabled(item)) return

        if (this.checkable) {
            this.#check(item, item.getAttribute('aria-checked') !== 'true')
        } else {
            this.#value = item.dataset.value
            this.#sync()
        }

        this.#announce()
    }

    // Checking cascades down to every enabled descendant; parents follow from their leaves.
    #check(item, checked) {
        const values = new Set(this.#value)
        const walk = node => {
            if (isDisabled(node)) return

            checked ? values.add(node.dataset.value) : values.delete(node.dataset.value)
            childItems(node).forEach(walk)
        }

        walk(item)
        this.#value = this.#settle(values)
        this.#sync()
    }

    // A checked parent checks everything below it; then parents follow their children.
    #cascade(values) {
        const set = new Set(values.map(String))

        for (const item of this.items) {
            if (set.has(item.dataset.value)) item.querySelectorAll(ITEM).forEach(child => ! isDisabled(child) && set.add(child.dataset.value))
        }

        return this.#settle(set)
    }

    // A parent is checked exactly when all its enabled children are.
    #settle(values) {
        const visit = item => {
            const children = childItems(item).filter(child => ! isDisabled(child))

            if (! children.length) return values.has(item.dataset.value)

            const states = children.map(visit)
            const all = states.every(Boolean)

            all ? values.add(item.dataset.value) : values.delete(item.dataset.value)

            return all
        }

        this.items.filter(item => item.parentElement === this || item.parentElement?.closest(ITEM) === null).forEach(visit)

        return this.items.map(item => item.dataset.value).filter(value => values.has(value))
    }

    #sync() {
        const set = (element, name, value) => {
            if (value === null) element.hasAttribute(name) && element.removeAttribute(name)
            else if (element.getAttribute(name) !== value) element.setAttribute(name, value)
        }

        const checked = new Set(this.checkable ? this.#value : [])
        const visible = this.#visible()
        const focusable = this.items.find(item => item.tabIndex === 0 && visible.includes(item))
            ?? visible.find(item => item.getAttribute('aria-selected') === 'true')
            ?? visible[0]

        for (const item of this.items) {
            const level = (() => {
                let depth = 1
                for (let node = item.parentElement?.closest(ITEM); node && node.closest('ui-tree') === this; node = node.parentElement?.closest(ITEM)) depth++
                return depth
            })()

            set(item, 'aria-level', String(level))

            if (isParent(item)) {
                const open = this.#expanded.has(item.dataset.value)
                const group = item.querySelector(':scope > [data-slot="tree-group"]')

                set(item, 'aria-expanded', String(open))
                if (group && group.hidden === open) group.hidden = ! open
            }

            if (this.checkable) {
                const children = item.querySelectorAll(ITEM)
                const some = [...children].some(child => checked.has(child.dataset.value))
                const state = checked.has(item.dataset.value) ? 'true' : some ? 'mixed' : 'false'
                const box = item.querySelector(':scope > [data-slot="tree-item-row"] [data-tree-check]')

                set(item, 'aria-checked', state)
                if (box) {
                    box.checked = state === 'true'
                    box.indeterminate = state === 'mixed'
                }
            } else {
                set(item, 'aria-selected', String(item.dataset.value === this.#value))
            }

            item.tabIndex = item === focusable ? 0 : -1
        }
    }

    // Items whose ancestors are all open, in order.
    #visible() {
        return this.items.filter(item => {
            for (let node = item.parentElement?.closest(ITEM); node && node.closest('ui-tree') === this; node = node.parentElement?.closest(ITEM)) {
                if (! this.#expanded.has(node.dataset.value)) return false
            }

            return true
        })
    }

    #focus(item) {
        if (! item) return

        this.items.forEach(other => (other.tabIndex = other === item ? 0 : -1))
        item.focus()
    }

    #onClick(event) {
        const item = event.target.closest?.(ITEM)

        if (! item || item.closest('ui-tree') !== this || ! event.target.closest('[data-slot="tree-item-row"]')) return

        if (event.target.closest('[data-slot="tree-item-toggle"]')) {
            this.expand(item)
            return
        }

        // The checkbox toggles itself natively; the tree decides its state.
        if (event.target.matches('[data-tree-check]')) event.preventDefault()

        if (! this.checkable && isParent(item)) this.expand(item)
        this.#choose(item)
        this.#focus(item)
    }

    #onKey(event) {
        const item = event.target.closest?.(ITEM)

        if (! item || item.closest('ui-tree') !== this) return

        const visible = this.#visible()
        const index = visible.indexOf(item)
        const go = target => {
            event.preventDefault()
            this.#focus(target)
        }

        switch (event.key) {
            case 'ArrowDown': return go(visible[index + 1])
            case 'ArrowUp': return go(visible[index - 1])
            case 'Home': return go(visible[0])
            case 'End': return go(visible.at(-1))
            case 'ArrowRight':
                event.preventDefault()
                if (! isParent(item)) return
                if (! this.#expanded.has(item.dataset.value)) return this.expand(item, true)
                return this.#focus(childItems(item)[0])
            case 'ArrowLeft': {
                event.preventDefault()
                if (isParent(item) && this.#expanded.has(item.dataset.value)) return this.expand(item, false)

                const parent = item.parentElement?.closest(ITEM)
                if (parent && parent.closest('ui-tree') === this) this.#focus(parent)
                return
            }
            case 'Enter':
            case ' ':
                event.preventDefault()
                this.#choose(item)
        }
    }
}

export class TreeSelectElement extends HTMLElement {
    #observer = new MutationObserver(() => this.render())

    connectedCallback() {
        this.trigger?.addEventListener('click', () => this.content?.togglePopover())
        this.content?.addEventListener('toggle', event => {
            this.trigger?.setAttribute('aria-expanded', String(event.newState === 'open'))

            if (event.newState === 'open') this.tree?.querySelector(`${ITEM}[tabindex="0"]`)?.focus()
        })

        // The tree's events become the picker's; one choice closes a single picker.
        for (const type of ['input', 'change']) {
            this.addEventListener(type, event => {
                if (event.target !== this.tree) return

                event.stopPropagation()
                this.render()
                if (type === 'change' && ! this.hasAttribute('data-checkable') && this.content?.matches(':popover-open')) {
                    this.content.hidePopover()
                    this.trigger?.focus()
                }
                this.dispatchEvent(new Event(type, { bubbles: true }))
            })
        }

        // Livewire morphs put back the server's label: show ours.
        if (this.trigger) this.#observer.observe(this.trigger, { childList: true, subtree: true, characterData: true })
    }

    disconnectedCallback() {
        this.#observer.disconnect()
    }

    get trigger() {
        return this.querySelector('[data-tree-select-trigger]')
    }

    get content() {
        return this.querySelector('[data-slot="tree-select-content"]')
    }

    get tree() {
        return this.querySelector('ui-tree')
    }

    get value() {
        return this.tree?.value ?? null
    }

    set value(value) {
        if (this.tree) this.tree.value = value
        this.render()
    }

    render() {
        const display = this.trigger?.querySelector('[data-slot="select-value"]')
        const labels = this.tree?.labels ?? []
        const text = labels.length ? labels.join(', ') : this.dataset.placeholder ?? ''

        if (display && display.textContent !== text) display.textContent = text
        if (this.trigger && this.trigger.hasAttribute('data-placeholder') !== (labels.length === 0)) this.trigger.toggleAttribute('data-placeholder', labels.length === 0)
    }
}

export function defineTree(registry = window.customElements) {
    if (! registry.get('ui-tree')) registry.define('ui-tree', TreeElement)
    if (! registry.get('ui-tree-select')) registry.define('ui-tree-select', TreeSelectElement)
}
