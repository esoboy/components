/*
 * <ui-tags-input> and <ui-number-input>
 *
 * Tags Input: typed text becomes a tag on Enter, a comma or leaving the field (pasted
 * lists split on commas and new lines); Backspace in the empty input removes the
 * last tag; duplicates and tags past data-max are ignored. .value is the list.
 *
 * Number Input: − and + (held, they repeat) and the arrow keys step by data-step
 * within data-min and data-max; typed text is read on change. .value is the number,
 * or null when empty.
 *
 * Both dispatch input/change from themselves, so wire:model binds on them.
 */

const SEPARATORS = /[,，\n]/

export class TagsInputElement extends HTMLElement {
    #tags = []
    #observer = new MutationObserver(() => this.#render())

    connectedCallback() {
        try {
            this.#tags = JSON.parse(this.dataset.value || '[]').map(String)
        } catch {
            this.#tags = []
        }

        // Listeners sit on the element: a morph may replace the input inside it.
        const fromInput = event => event.target === this.input

        this.addEventListener('keydown', event => {
            const input = this.input

            if (! fromInput(event) || event.isComposing) return

            if (event.key === 'Enter' || event.key === ',') {
                if (input.value.trim() === '' && event.key === 'Enter') return

                event.preventDefault()
                this.#add(input.value)
            } else if (event.key === 'Backspace' && input.value === '' && this.#tags.length) {
                this.#set(this.#tags.slice(0, -1))
            }
        })

        this.addEventListener('input', event => {
            if (! fromInput(event)) return

            event.stopPropagation()
            if (SEPARATORS.test(this.input.value)) this.#add(this.input.value)
        })

        this.addEventListener('focusout', event => fromInput(event) && this.input.value.trim() && this.#add(this.input.value))

        this.addEventListener('click', event => {
            const remove = event.target.closest?.('[data-tags-input-remove]')

            if (remove) {
                const tag = remove.closest('[data-slot="tags-input-tag"]')
                const index = [...this.querySelectorAll(':scope > [data-slot="tags-input-tag"]')].indexOf(tag)

                this.#set(this.#tags.filter((_, position) => position !== index))
                this.input?.focus()
            } else if (event.target === this) {
                this.input?.focus()
            }
        })

        this.#render()

        // Livewire morphs put back the server's tags: show ours.
        this.#observer.observe(this, { childList: true })
    }

    disconnectedCallback() {
        this.#observer.disconnect()
    }

    get input() {
        return this.querySelector('[data-slot="tags-input-input"]')
    }

    get value() {
        return [...this.#tags]
    }

    set value(value) {
        this.#tags = Array.isArray(value) ? value.map(String) : []
        this.#render()
    }

    #add(text) {
        const max = Number(this.dataset.max) || Infinity
        const tags = [...this.#tags]

        for (const part of text.split(SEPARATORS).map(part => part.trim()).filter(Boolean)) {
            if (! tags.includes(part) && tags.length < max) tags.push(part)
        }

        if (this.input) this.input.value = ''
        if (tags.length !== this.#tags.length) this.#set(tags)
    }

    #set(tags) {
        this.#tags = tags
        this.#render()
        this.dispatchEvent(new Event('input', { bubbles: true }))
        this.dispatchEvent(new Event('change', { bubbles: true }))
    }

    #render() {
        const template = this.querySelector(':scope > template[data-tags-input-template]')
        const current = [...this.querySelectorAll(':scope > [data-slot="tags-input-tag"]')]
        const same = current.length === this.#tags.length
            && current.every((tag, index) => tag.querySelector('[data-tags-input-remove]') && tag.querySelector('[data-tags-input-label]')?.textContent === this.#tags[index])

        if (! same && template) {
            this.#observer.disconnect()

            current.forEach(tag => tag.remove())

            for (const text of this.#tags) {
                const tag = template.content.firstElementChild.cloneNode(true)

                tag.querySelector('[data-tags-input-label]').textContent = text
                this.insertBefore(tag, this.input)
            }

            this.#writeHidden()
            this.#observer.observe(this, { childList: true })
        }

        const full = this.#tags.length >= (Number(this.dataset.max) || Infinity)

        if (this.input) this.input.disabled = this.hasAttribute('data-disabled') || full
    }

    // Classic form posts get the tags as name[] inputs.
    #writeHidden() {
        if (! this.dataset.name) return

        this.querySelectorAll(':scope > input[type="hidden"]').forEach(input => input.remove())

        for (const tag of this.#tags) this.append(Object.assign(document.createElement('input'), { type: 'hidden', name: `${this.dataset.name}[]`, value: tag }))
    }
}

export class NumberInputElement extends HTMLElement {
    #value = null
    #repeat = 0

    connectedCallback() {
        this.#value = this.#parse(this.input?.value)

        // Listeners sit on the element: a morph may replace the input inside it.
        const fromInput = event => event.target === this.input

        this.addEventListener('input', event => fromInput(event) && event.stopPropagation())
        this.addEventListener('change', event => {
            if (! fromInput(event)) return

            event.stopPropagation()
            this.#commit(this.#parse(this.input.value))
        })
        this.addEventListener('keydown', event => {
            if (! fromInput(event)) return

            const steps = { ArrowUp: 1, ArrowDown: -1, PageUp: 10, PageDown: -10 }[event.key]

            if (steps) {
                event.preventDefault()
                this.#stepBy(steps)
            } else if (event.key === 'Home' && this.dataset.min !== undefined) {
                event.preventDefault()
                this.#commit(Number(this.dataset.min))
            } else if (event.key === 'End' && this.dataset.max !== undefined) {
                event.preventDefault()
                this.#commit(Number(this.dataset.max))
            }
        })

        this.addEventListener('pointerdown', event => {
            const button = event.target.closest?.('[data-number-input]')

            if (! button || button.disabled || event.button !== 0) return

            event.preventDefault()

            const direction = button.dataset.numberInput === 'increment' ? 1 : -1

            this.#stepBy(direction)

            // Held, it repeats: after a pause, then quickly.
            clearTimeout(this.#repeat)
            const again = delay => (this.#repeat = setTimeout(() => {
                this.#stepBy(direction)
                again(50)
            }, delay))

            again(400)
        })

        for (const type of ['pointerup', 'pointerleave', 'pointercancel']) {
            this.addEventListener(type, () => clearTimeout(this.#repeat))
        }
    }

    disconnectedCallback() {
        clearTimeout(this.#repeat)
    }

    get input() {
        return this.querySelector('[data-slot="input-group-control"]')
    }

    get value() {
        return this.#value
    }

    set value(value) {
        this.#value = this.#parse(value)
        this.#render()
    }

    #parse(value) {
        if (value === null || value === undefined || String(value).trim() === '') return null

        const number = Number(String(value).replace(',', '.'))

        return Number.isFinite(number) ? this.#clamp(number) : this.#value
    }

    #clamp(number) {
        const min = this.dataset.min !== undefined ? Number(this.dataset.min) : -Infinity
        const max = this.dataset.max !== undefined ? Number(this.dataset.max) : Infinity

        return Math.min(max, Math.max(min, number))
    }

    // Steps keep the step's decimals, so 0.1 + 0.2 shows 0.3.
    #stepBy(steps) {
        const step = Number(this.dataset.step) || 1
        const decimals = (String(step).split('.')[1] ?? '').length
        const start = this.#value ?? (this.dataset.min !== undefined ? Number(this.dataset.min) : 0)
        const next = this.#value === null ? start : start + steps * step

        this.#commit(Number(this.#clamp(next).toFixed(decimals)))
    }

    #commit(value) {
        const changed = value !== this.#value

        this.#value = value
        this.#render()

        if (changed) {
            this.dispatchEvent(new Event('input', { bubbles: true }))
            this.dispatchEvent(new Event('change', { bubbles: true }))
        }
    }

    #render() {
        const input = this.input
        const text = this.#value === null ? '' : String(this.#value)

        if (input && input.value !== text) input.value = text
        if (input) this.#value === null ? input.removeAttribute('aria-valuenow') : input.setAttribute('aria-valuenow', text)

        const min = this.dataset.min !== undefined ? Number(this.dataset.min) : -Infinity
        const max = this.dataset.max !== undefined ? Number(this.dataset.max) : Infinity
        const disabled = input?.disabled ?? false

        for (const button of this.querySelectorAll('[data-number-input]')) {
            button.disabled = disabled || (this.#value !== null && (button.dataset.numberInput === 'increment' ? this.#value >= max : this.#value <= min))
        }
    }
}

export function defineInputs(registry = window.customElements) {
    if (! registry.get('ui-tags-input')) registry.define('ui-tags-input', TagsInputElement)
    if (! registry.get('ui-number-input')) registry.define('ui-number-input', NumberInputElement)
}
