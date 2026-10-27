/*
 * <eso-emoji-picker>
 *
 * Emojibase's emojis in their categories, fetched from data-src (one JSON per language)
 * the first time a picker is shown, once per page, and narrowed to what the device draws.
 * The search keeps the emojis whose label or a keyword holds it, label matches first as
 * Frimousse ranks them. The arrow keys move the active emoji, which the footer names, and
 * Enter or a click picks it: emoji-select reports { emoji, label } from the element, then
 * the popover around the picker closes unless the event was canceled. Opening that
 * popover focuses the search; closing it clears the search.
 */

import { define } from '../support/element.js'
import { clone, readTemplates } from '../support/templates.js'

const ARROWS = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight']
const FLAG = /^[\u{1F1E6}-\u{1F1FF}]{2}$/u

const loads = new Map()
let canvas = null
let count = 0

/**
 * The data at a URL, cut down to what the device draws; fetched once per page.
 */
function load(url) {
    if (! loads.has(url)) {
        loads.set(url, fetch(url)
            .then(response => (response.ok ? response.json() : Promise.reject(new Error(`${response.status} ${url}`))))
            .then(drawable)
            .catch(error => {
                loads.delete(url)
                throw error
            }))
    }

    return loads.get(url)
}

/**
 * Leaves out the Emoji versions newer than the device draws (tested on each version's
 * first emoji) and, where it shows country flags as letters, those flags.
 */
function drawable(data) {
    const samples = new Map()

    for (const emojis of Object.values(data)) {
        for (const [emoji, , , version] of emojis) if (! samples.has(version)) samples.set(version, emoji)
    }

    const newest = [...samples.keys()].sort((a, b) => b - a).find(version => draws(samples.get(version))) ?? Infinity
    const flags = draws('\u{1F1FA}\u{1F1F3}')

    return Object.fromEntries(Object.entries(data).map(([category, emojis]) => [
        category,
        emojis.filter(([emoji, , , version]) => version <= newest && (flags || ! FLAG.test(emoji))),
    ]))
}

/**
 * Whether the device draws an emoji as one colour glyph. A missing emoji comes out as a
 * box in the text colour, a missing sequence as several glyphs. Without a canvas to ask,
 * it says yes.
 */
function draws(emoji) {
    canvas ??= document.createElement('canvas')

    const context = canvas.getContext('2d', { willReadFrequently: true })

    if (! context) return true

    const size = 20

    canvas.width = size * 4
    canvas.height = size * 2
    context.font = `${size}px "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif`
    context.textBaseline = 'top'

    if (context.measureText(emoji).width > context.measureText('\u{1F600}').width * 1.5) return false

    const pixels = colour => {
        context.clearRect(0, 0, canvas.width, canvas.height)
        context.fillStyle = colour
        context.fillText(emoji, 0, 0)

        return context.getImageData(0, 0, canvas.width, canvas.height).data
    }

    const red = pixels('#f00')
    const blue = pixels('#00f')

    return red.some(value => value) && red.every((value, index) => value === blue[index])
}

export class EmojiPickerElement extends HTMLElement {
    #ready = false
    #loading = false
    #categories = []
    #active = null
    #observer = null
    #popover = null
    #prefix = ''

    connectedCallback() {
        this.#popover = this.parentElement?.closest('[popover]') ?? null
        this.#popover?.addEventListener('toggle', this.#toggled)

        if (! this.#categories.length) this.#whenShown()
        if (this.#ready) return

        this.#ready = true
        this.#prefix = this.id || `emoji-picker-${++count}`

        this.addEventListener('input', event => {
            if (event.target === this.search) this.#filter()
        })

        this.addEventListener('keydown', event => this.#key(event))

        this.addEventListener('click', event => {
            const emoji = event.target.closest?.('[data-slot="emoji-picker-emoji"]')

            if (emoji) this.#pick(emoji)
        })

        this.addEventListener('pointermove', event => {
            const emoji = event.target.closest?.('[data-slot="emoji-picker-emoji"]')

            if (emoji) this.#activate(emoji)
        })

        this.addEventListener('pointerleave', () => this.#activate(null))
    }

    disconnectedCallback() {
        this.#observer?.disconnect()
        this.#popover?.removeEventListener('toggle', this.#toggled)
    }

    get search() {
        return this.querySelector('[data-slot="emoji-picker-search"]')
    }

    get viewport() {
        return this.querySelector('[data-slot="emoji-picker-viewport"]')
    }

    get list() {
        return this.querySelector('[data-slot="emoji-picker-list"]')
    }

    get active() {
        return this.#active
    }

    #toggled = event => {
        if (event.newState === 'open') {
            this.search?.focus()
        } else if (this.search?.value) {
            this.search.value = ''
            this.#filter()
        }
    }

    // The data loads when the picker first shows: in a closed popover, when it opens.
    #whenShown() {
        if (! ('IntersectionObserver' in window)) return this.#load()

        this.#observer?.disconnect()
        this.#observer = new IntersectionObserver(entries => {
            if (! entries.some(entry => entry.isIntersecting)) return

            this.#observer.disconnect()
            this.#load()
        })
        this.#observer.observe(this)
    }

    async #load() {
        if (this.#loading || ! this.list) return

        this.#loading = true

        try {
            this.#build(await load(this.dataset.src))
        } catch {
            this.querySelector('[data-slot="emoji-picker-loading"]')?.toggleAttribute('hidden', true)
            this.querySelector('[data-slot="emoji-picker-empty"]')?.toggleAttribute('hidden', false)
        } finally {
            this.#loading = false
        }
    }

    #build(data) {
        const template = readTemplates(this.viewport).emoji

        this.#categories = [...this.list.querySelectorAll('[data-slot="emoji-picker-category"]')].map(element => {
            const grid = element.querySelector('[data-slot="emoji-picker-emojis"]')
            const emojis = (data[element.dataset.category] ?? []).map(([emoji, label, keywords]) => {
                const button = clone(template)

                button.textContent = emoji
                button.setAttribute('aria-label', label)

                return { button, label: label.toLowerCase(), keywords: keywords.toLowerCase().split('|'), score: 0 }
            })

            grid.replaceChildren(...emojis.map(({ button }) => button))

            return { element, grid, emojis }
        })

        this.list.id ||= `${this.#prefix}-list`
        this.search?.setAttribute('aria-controls', this.list.id)
        this.querySelector('[data-slot="emoji-picker-loading"]')?.toggleAttribute('hidden', true)
        this.list.hidden = false
        this.#filter()
    }

    #filter() {
        const query = (this.search?.value ?? '').trim().toLowerCase()
        const columns = Number(this.dataset.columns) || 9
        let found = false

        for (const { element, grid, emojis } of this.#categories) {
            for (const emoji of emojis) {
                emoji.score = query ? (emoji.label.includes(query) ? 10 : 0) + emoji.keywords.filter(keyword => keyword.includes(query)).length : 1
                emoji.button.hidden = ! emoji.score
            }

            // The best matches first; without a search, Emojibase's order again.
            const shown = emojis.filter(emoji => emoji.score)

            grid.append(...(query ? shown.sort((a, b) => b.score - a.score) : emojis).map(({ button }) => button))
            element.hidden = ! shown.length
            element.style.setProperty('--emoji-picker-rows', Math.ceil(shown.length / columns))
            found ||= shown.length > 0
        }

        this.querySelector('[data-slot="emoji-picker-empty"]')?.toggleAttribute('hidden', ! this.#categories.length || found)

        if (this.viewport) this.viewport.scrollTop = 0

        this.#activate(query ? this.#rows()[0]?.[0] : null)
    }

    // The emojis shown, row by row as the grid lays them out, category after category.
    #rows() {
        const columns = Number(this.dataset.columns) || 9

        return [...this.list.querySelectorAll('[data-slot="emoji-picker-category"]:not([hidden])')].flatMap(category => {
            const emojis = [...category.querySelectorAll('[data-slot="emoji-picker-emoji"]:not([hidden])')]

            return Array.from({ length: Math.ceil(emojis.length / columns) }, (_, row) => emojis.slice(row * columns, (row + 1) * columns))
        })
    }

    #key(event) {
        if (! event.target.closest?.('[data-slot="emoji-picker-search"], [data-slot="emoji-picker-viewport"]')) return

        if (event.key === 'Enter' && this.#active) {
            event.preventDefault()
            this.#pick(this.#active)

            return
        }

        if (! ARROWS.includes(event.key)) return

        // In the search, left and right move the caret until an emoji is active.
        if (! this.#active && event.target === this.search && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) return

        event.preventDefault()
        this.#move(event.key)
    }

    #move(key) {
        const rows = this.#rows()
        const row = rows.findIndex(emojis => emojis.includes(this.#active))

        if (row < 0) return this.#activate(rows[0]?.[0], true)

        if (key === 'ArrowLeft' || key === 'ArrowRight') {
            const emojis = rows.flat()

            return this.#activate(emojis[emojis.indexOf(this.#active) + (key === 'ArrowRight' ? 1 : -1)] ?? this.#active, true)
        }

        const column = rows[row].indexOf(this.#active)
        const next = rows[row + (key === 'ArrowDown' ? 1 : -1)]

        if (next) this.#activate(next[Math.min(column, next.length - 1)], true)
    }

    #activate(emoji, scroll = false) {
        emoji ??= null

        if (emoji === this.#active) return

        this.#active?.removeAttribute('data-active')
        this.#active?.setAttribute('aria-selected', 'false')
        this.#active = emoji

        if (emoji) {
            emoji.id ||= `${this.#prefix}-emoji-${++count}`
            emoji.setAttribute('data-active', '')
            emoji.setAttribute('aria-selected', 'true')
            this.search?.setAttribute('aria-activedescendant', emoji.id)

            if (scroll) emoji.scrollIntoView({ block: 'nearest' })
        } else {
            this.search?.removeAttribute('aria-activedescendant')
        }

        const part = slot => this.querySelector(`[data-slot="emoji-picker-${slot}"]`)

        if (part('active-emoji')) part('active-emoji').textContent = emoji?.textContent ?? ''
        if (part('active-label')) part('active-label').textContent = emoji?.getAttribute('aria-label') ?? ''

        part('active-emoji')?.toggleAttribute('hidden', ! emoji)
        part('active-label')?.toggleAttribute('hidden', ! emoji)
        part('placeholder')?.toggleAttribute('hidden', !! emoji)
    }

    #pick(emoji) {
        const picked = new CustomEvent('emoji-select', {
            bubbles: true,
            cancelable: true,
            detail: { emoji: emoji.textContent, label: emoji.getAttribute('aria-label') },
        })

        if (this.dispatchEvent(picked) && this.#popover?.matches(':popover-open')) this.#popover.hidePopover()
    }
}

export function register() {
    define('eso-emoji-picker', EmojiPickerElement)
}
