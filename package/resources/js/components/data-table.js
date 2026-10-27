/*
 * <eso-data-table>
 *
 * The select-all checkbox checks or clears every row checkbox (each announces its own
 * change, so wire:model sees them all) and shows mixed while only some are checked.
 * Rows of checked boxes get data-state="selected", as TanStack rows do in shadcn.
 */

import { define } from '../support/element.js'

const ALL = '[data-data-table-select-all]'
const ROW = '[data-data-table-select-row]'

export class DataTableElement extends HTMLElement {
    #observer = new MutationObserver(() => this.#sync())

    connectedCallback() {
        this.addEventListener('change', event => {
            if (event.target.matches?.(ALL) && this.#owns(event.target)) this.#selectAll(event.target.checked)
            else if (event.target.matches?.(ROW)) this.#sync()
        })

        this.#sync()

        // Livewire morphs bring new rows and the server's checked state.
        this.#observer.observe(this, { childList: true, subtree: true, attributes: true, attributeFilter: ['checked', 'data-state'] })
    }

    disconnectedCallback() {
        this.#observer.disconnect()
    }

    get rows() {
        return [...this.querySelectorAll(ROW)].filter(box => this.#owns(box) && ! box.disabled)
    }

    #owns(element) {
        return element.closest('eso-data-table') === this
    }

    #selectAll(checked) {
        for (const box of this.rows.filter(box => box.checked !== checked)) {
            box.checked = checked
            box.dispatchEvent(new Event('input', { bubbles: true }))
            box.dispatchEvent(new Event('change', { bubbles: true }))
        }

        this.#sync()
    }

    #sync() {
        const rows = this.rows
        const checked = rows.filter(box => box.checked).length

        for (const all of [...this.querySelectorAll(ALL)].filter(box => this.#owns(box))) {
            all.checked = rows.length > 0 && checked === rows.length
            all.indeterminate = checked > 0 && checked < rows.length
        }

        for (const box of rows) {
            const row = box.closest('tr')
            const state = box.checked ? 'selected' : null

            if (row && row.getAttribute('data-state') !== state) state ? row.setAttribute('data-state', state) : row.removeAttribute('data-state')
        }
    }
}

export function register() {
    define('eso-data-table', DataTableElement)
}
