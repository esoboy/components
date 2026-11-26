// An input for typed values which a native text field cannot represent.
export function registerModelFields() {
    if (customElements.get('ui-model-field')) return
    customElements.define(
        'ui-model-field',
        class extends HTMLElement {
            static formAssociated = true
            internals = this.attachInternals()
            connectedCallback() {
                this.syncForm()
            }
            syncForm() {
                if (!this.hasAttribute('data-form-field')) return
                const value = this.value
                const entries = value == null ? [] : [value].flat()
                const valid = entries.length >= Number(this.dataset.minValues ?? 0)
                this.internals.setValidity(
                    valid ? {} : { valueMissing: true },
                    valid ? '' : this.dataset.requiredMessage,
                )
                const data = new FormData()
                const name = this.getAttribute('name')
                if (name)
                    for (const entry of entries)
                        data.append(name + (Array.isArray(value) ? '[]' : ''), entry)
                this.internals.setFormValue(data)
            }
            get value() {
                return this.editedValue === undefined
                    ? JSON.parse(this.dataset.initial)
                    : this.editedValue
            }
            set value(value) {
                this.editedValue = value
                this.syncForm()
            }
            edit(value, commit = true) {
                this.value = value
                for (const type of commit ? ['input', 'change'] : ['input'])
                    this.dispatchEvent(new CustomEvent(type, { detail: value, bubbles: true }))
            }
        },
    )
    document.addEventListener(
        'click',
        (event) => {
            const button = event.target.closest?.(
                '[data-ui-edit]:not(:disabled, [aria-disabled="true"])',
            )
            const field = button && document.getElementById(button.dataset.uiEdit)
            if (!field) return
            if (field instanceof HTMLSelectElement) {
                field.value = JSON.parse(button.dataset.uiNext)
                field.dispatchEvent(new Event('change', { bubbles: true }))
            } else if (button.hasAttribute('data-choice-option')) {
                const select = field.querySelector('select')
                // Reflect the current typed value before handling another click or a parent refill.
                const selected = (field.value ?? []).map((item) => JSON.stringify(item))
                for (const item of select.options) item.selected = selected.includes(item.value)
                const option = select.options.namedItem(button.dataset.choiceOption)
                option.selected = !option.selected
                if (option.selected) select.add(option)
                field.edit(Array.from(select.selectedOptions, (item) => JSON.parse(item.value)))
            } else if (button.dataset.rangeDate) {
                const { rangeDate, lower, upper } = button.dataset
                const start = field.value?.length === 1 ? field.value[0] : null
                const same = start && (!lower || start >= lower) && (!upper || start <= upper)
                if (!start || same || start === button.dataset.invalidAnchor)
                    field.edit(same ? [start, rangeDate].sort() : [rangeDate])
            } else {
                let next = JSON.parse(button.dataset.uiNext)
                const start = Number(button.dataset.part)
                const current = field.value
                if (
                    Number.isInteger(start) &&
                    typeof current === 'string' &&
                    current.length === next?.length
                ) {
                    const end = start + Number(button.dataset.width)
                    next = current.slice(0, start) + next.slice(start, end) + current.slice(end)
                }
                field.edit(next)
            }
        },
        true,
    )
}
