/*
 * <eso-date-picker>
 *
 * A popover trigger showing the chosen date(s) and a <eso-calendar> inside. .value
 * proxies the calendar and input/change are dispatched from the picker itself, so
 * wire:model binds on it.
 */

import { announce, define } from '../support/element.js'
import { fromKey } from './calendar.js'

export class DatePickerElement extends HTMLElement {
    #observer = null

    connectedCallback() {
        this.addEventListener('input', event => event.target === this.calendar && this.#announce(event, 'input'))
        this.addEventListener('change', event => event.target === this.calendar && this.#announce(event, 'change'))

        // A preset sets the calendar and shows its month.
        this.addEventListener('click', event => {
            const preset = event.target.closest?.('[data-date-picker-preset]')

            if (! preset || ! this.calendar) return

            this.calendar.value = JSON.parse(preset.dataset.datePickerPreset)
            this.render()
            announce(this)
        })

        this.render()

        // Livewire morphs reset the trigger to the server's placeholder: put the date back.
        this.#observer = new MutationObserver(() => this.render())
        this.#observer.observe(this, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['aria-invalid', 'aria-describedby'] })
    }

    disconnectedCallback() {
        this.#observer?.disconnect()
    }

    get calendar() {
        return this.querySelector('eso-calendar')
    }

    get trigger() {
        return this.querySelector('[data-date-picker-trigger]')
    }

    get value() {
        return this.calendar?.value ?? null
    }

    set value(value) {
        if (this.calendar) this.calendar.value = value

        this.render()
    }

    render() {
        const display = this.querySelector('[data-slot="date-picker-value"]')
        const trigger = this.trigger

        // The calendar may not be upgraded yet; its first render triggers ours again.
        if (! display || ! trigger || ! this.calendar?.month) return

        const text = this.#format(this.calendar.value)
        const empty = text === ''

        if (display.textContent !== (empty ? this.dataset.placeholder ?? '' : text)) display.textContent = empty ? this.dataset.placeholder ?? '' : text
        if (trigger.dataset.empty !== String(empty)) trigger.dataset.empty = String(empty)

        // The picker carries the field's validation state; screen readers need it on the trigger.
        for (const name of ['aria-invalid', 'aria-describedby']) {
            const value = this.getAttribute(name)

            if (value === null) trigger.hasAttribute(name) && trigger.removeAttribute(name)
            else if (trigger.getAttribute(name) !== value) trigger.setAttribute(name, value)
        }
    }

    #format(value) {
        const locale = this.dataset.locale || undefined
        const long = key => fromKey(key).toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' })
        const short = key => fromKey(key).toLocaleDateString(locale, { year: 'numeric', month: 'short', day: '2-digit' })

        if (Array.isArray(value)) return value.map(short).join(', ')
        if (value && typeof value === 'object') return value.from ? [value.from, value.to].filter(Boolean).map(short).join(' - ') : ''

        return value ? long(value) : ''
    }

    #announce(event, type) {
        event.stopPropagation()
        this.render()
        this.dispatchEvent(new Event(type, { bubbles: true }))
    }
}

export function register() {
    define('eso-date-picker', DatePickerElement)
}
