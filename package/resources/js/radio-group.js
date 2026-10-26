/*
 * <ui-radio-group>
 *
 * Exposes the checked radio as .value and re-dispatches its radios' input/change
 * events from the group itself, so wire:model / x-model bind on the group the way
 * shadcn puts value on RadioGroup. (Livewire v4 models only listen to events whose
 * target is the bound element.) Radios without a name get the group's, which keeps
 * native keyboard grouping.
 */

let groups = 0

export class RadioGroupElement extends HTMLElement {
    #fallbackName = `ui-radio-group-${++groups}`
    #observer = null

    constructor() {
        super()

        for (const type of ['input', 'change']) {
            this.addEventListener(type, event => {
                if (event.target !== this && this.radios.includes(event.target)) {
                    this.dispatchEvent(new Event(type, { bubbles: true }))
                }
            })
        }
    }

    connectedCallback() {
        this.#nameRadios()

        // Livewire morphs can add radios or strip the names we assigned.
        this.#observer = new MutationObserver(() => this.#nameRadios())
        this.#observer.observe(this, { childList: true, subtree: true, attributes: true, attributeFilter: ['name'] })
    }

    disconnectedCallback() {
        this.#observer?.disconnect()
    }

    get radios() {
        return [...this.querySelectorAll('input[type="radio"]')].filter(radio => radio.closest('ui-radio-group') === this)
    }

    get value() {
        return this.radios.find(radio => radio.checked)?.value ?? null
    }

    set value(value) {
        const target = value === null || value === undefined ? null : String(value)

        this.radios.forEach(radio => (radio.checked = radio.value === target))
    }

    #nameRadios() {
        const name = this.getAttribute('name') || this.#fallbackName

        this.radios.forEach(radio => radio.name || (radio.name = name))
    }
}

export function defineRadioGroup(registry = window.customElements) {
    if (! registry.get('ui-radio-group')) registry.define('ui-radio-group', RadioGroupElement)
}
