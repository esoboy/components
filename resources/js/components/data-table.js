export function dataTable() {
    return {
        widths: {},
        reflectSelection(input, value) {
            const selected = new Set((value ?? []).map(String))
            const page = JSON.parse(input.dataset.pageKeys)
            const count = page.filter((id) => selected.has(String(id))).length
            input.checked = count > 0 && count === page.length
            input.indeterminate = count > 0 && count < page.length
        },
        get selectionField() {
            return this.$root.querySelector(':scope > [data-table-selection]')
        },
        selectRows(control) {
            const field = this.selectionField
            for (const option of field.options) {
                if (
                    !control ||
                    (control.hasAttribute('data-row-value')
                        ? option.value === control.dataset.rowValue
                        : option.hasAttribute('data-page'))
                )
                    option.selected = !!control?.checked
            }
            field.dispatchEvent(new Event('change', { bubbles: true }))
        },
        forward(type) {
            this.selectionField.dispatchEvent(
                type === 'keydown'
                    ? new KeyboardEvent(type, { key: 'Enter', bubbles: true })
                    : new FocusEvent(type),
            )
        },
        measure(head) {
            this.widths[head.dataset.widthVar] = head.getBoundingClientRect().width + 'px'
        },
    }
}
