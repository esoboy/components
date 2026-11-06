/*
 * <eso-data-table>
 *
 * The select-all checkbox checks or clears every row checkbox (each announces its own
 * change, so wire:model sees them all) and shows mixed while only some are checked.
 * Rows of checked boxes get data-state="selected", as TanStack rows do in shadcn.
 *
 * Row Toggles expand a row: its Row Detail, or the rows whose data-parent is its
 * data-row-id (shown only while every ancestor is expanded). Expanded rows are kept
 * by id, so they stay expanded through morphs and pages.
 *
 * Sticky cells (data-sticky="start" / "end") get --sticky-offset, the width of the
 * sticky cells before them, and a scrolled table container says which edges have
 * more to scroll to (data-scroll-start / data-scroll-end) for their shadows.
 *
 * A merged cell (rowspan) is highlighted with every row it spans, not just its own.
 */

const ALL = '[data-data-table-select-all]'
const ROW = '[data-data-table-select-row]'
const TOGGLE = '[data-data-table-row-toggle]'

// Sets or removes an attribute only when it changes, so the observer isn't woken for nothing.
const set = (element, name, value) => {
    if (value === null) element.hasAttribute(name) && element.removeAttribute(name)
    else if (element.getAttribute(name) !== value) element.setAttribute(name, value)
}

import { listen, disconnected } from './interaction.js'

export default (binding = {}) => ({
    _expanded: new Set(),
    _serverExpanded: new Map(),
    _hovered: null,
    _observer: null,
    _resizes: null,

    init() {
        this._observer = new MutationObserver(() => this._refresh())
        this._resizes = new ResizeObserver(() => this._stick())

        listen(this, this.$el, 'change', event => {
            if (event.target.matches?.(ALL) && this._owns(event.target)) this._selectAll(event.target.checked)
            else if (event.target.matches?.(ROW)) this._sync()
        })

        listen(this, this.$el, 'click', event => {
            const toggle = event.target.closest?.(TOGGLE)
            const id = toggle && this._owns(toggle) ? toggle.closest('[data-row-id]')?.dataset.rowId : undefined

            if (id === undefined) return

            this._expanded.has(id) ? this._expanded.delete(id) : this._expanded.add(id)
            this._expand()
        })

        listen(this, this.$el, 'scroll', event => event.target.matches?.('[data-slot="table-container"]') && this._edges(event.target), { capture: true, passive: true })
        listen(this, this.$el, 'pointerover', event => this._hover(event.target.closest?.('tbody > tr') ?? null))
        listen(this, this.$el, 'pointerleave', () => this._hover(null))

        this._refresh()

        // Livewire morphs bring new rows, the server's checked state, and reset what is set here.
        this._observer.observe(this.$el, { childList: true, subtree: true, attributes: true, attributeFilter: ['checked', 'data-state', 'hidden', 'aria-expanded', 'data-expanded', 'data-leaf', 'style', 'data-scroll-start', 'data-scroll-end'] })
    },

    destroy() {
        disconnected(this)
        this._observer.disconnect()
        this._resizes.disconnect()
    },

    get rows() {
        return [...this.$el.querySelectorAll(ROW)].filter(box => this._owns(box) && ! box.disabled)
    },

    _owns(element) {
        return element.closest('eso-data-table') === this.$el
    },

    _refresh() {
        this._sync()
        this._expand()
        this._stick()

        for (const container of this.$el.querySelectorAll('[data-slot="table-container"]')) {
            if (this._owns(container)) this._edges(container)
        }
    },

    _selectAll(checked) {
        this.$wire.selectRows(this.rows.map(box => box.value), checked)
    },

    _sync() {
        const rows = this.rows
        const checked = rows.filter(box => box.checked).length

        for (const all of [...this.$el.querySelectorAll(ALL)].filter(box => this._owns(box))) {
            all.checked = rows.length > 0 && checked === rows.length
            all.indeterminate = checked > 0 && checked < rows.length
        }

        for (const box of rows) {
            const row = box.closest('tr')

            if (row) set(row, 'data-state', box.checked ? 'selected' : null)
        }
    },

    _expand() {
        const rows = [...this.$el.querySelectorAll('tr[data-row-id]')].filter(row => this._owns(row))
        const byId = new Map(rows.map(row => [row.dataset.rowId, row]))
        const parents = new Set(rows.map(row => row.dataset.parent).filter(Boolean))
        const details = new Map([...this.$el.querySelectorAll('tr[data-detail-for]')].filter(detail => this._owns(detail)).map(detail => [detail.dataset.detailFor, detail]))

        for (const row of rows) {
            const id = row.dataset.rowId
            const expanded = row.getAttribute('data-expanded')
            if (this._serverExpanded.get(id) === expanded) continue

            this._serverExpanded.set(id, expanded)
            if (expanded === null) continue
            expanded === 'false' ? this._expanded.delete(id) : this._expanded.add(id)
        }

        const shown = (row, depth = 0) => {
            const parent = row.dataset.parent

            if (! parent || depth > 50) return true

            return this._expanded.has(parent) && (! byId.has(parent) || shown(byId.get(parent), depth + 1))
        }

        for (const row of rows) {
            const id = row.dataset.rowId
            const visible = shown(row)
            const detail = details.get(id)
            const expandable = parents.has(id) || Boolean(detail)
            const open = expandable && this._expanded.has(id)
            const toggle = row.querySelector(TOGGLE)

            set(row, 'hidden', visible ? null : '')
            if (detail) set(detail, 'hidden', visible && open ? null : '')

            if (toggle) {
                set(toggle, 'aria-expanded', String(open))
                set(toggle, 'data-leaf', expandable ? null : '')
            }
        }
    },

    // Each sticky cell sits after the sticky cells before it on its edge.
    _stick() {
        for (const table of this.$el.querySelectorAll('table')) {
            if (! this._owns(table) || ! table.querySelector('[data-sticky]')) continue

            this._resizes.observe(table)

            for (const row of table.rows) {
                const cells = [...row.cells]

                for (const [edge, order] of [['start', cells], ['end', [...cells].reverse()]]) {
                    let offset = 0

                    for (const cell of order.filter(cell => cell.dataset.sticky === edge)) {
                        if (cell.style.getPropertyValue('--sticky-offset') !== `${offset}px`) cell.style.setProperty('--sticky-offset', `${offset}px`)
                        offset += cell.getBoundingClientRect().width
                    }
                }
            }
        }
    },

    _edges(container) {
        const scrolled = Math.abs(container.scrollLeft)
        const end = container.scrollWidth - container.clientWidth

        set(container, 'data-scroll-start', scrolled > 0 ? '' : null)
        set(container, 'data-scroll-end', scrolled < end - 1 ? '' : null)
    },

    _hover(row) {
        if (row === this._hovered) return

        this._hovered = row

        for (const cell of this.$el.querySelectorAll('[data-span-hover]')) cell.removeAttribute('data-span-hover')

        // The cells merged down from the rows above cover this one too.
        for (let above = row?.previousElementSibling, distance = 1; above && distance < 100; above = above.previousElementSibling, distance++) {
            for (const cell of above.cells ?? []) {
                if (cell.rowSpan > distance) cell.setAttribute('data-span-hover', '')
            }
        }
    },
})
