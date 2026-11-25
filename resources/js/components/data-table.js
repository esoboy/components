export function dataTable() {
    let pendingRows = []
    return {
        widths: {},
        reflectSelection(input, value) {
            const selected = new Set((value ?? []).map(String))
            const page = JSON.parse(input.dataset.pageKeys)
            const count = page.filter((id) => selected.has(id)).length
            input.checked = count > 0 && count === page.length
            input.indeterminate = count > 0 && count < page.length
        },
        prepareSelection() {
            const model = this.$root.dataset.selectionModel
            if (this.$wire.$get(model) == null) this.$wire.$set(model, [], false)
        },
        selectPage(input) {
            this.prepareSelection()
            const table = input.closest('table')
            const rows = [...table.querySelectorAll('[data-ui-select-row]:enabled')].filter(
                (row) => row.closest('table') === table,
            )
            // Each real checkbox applies its own wire:model modifiers and value type.
            pendingRows = rows.filter((row) => row.checked !== input.checked)
            for (const row of pendingRows) {
                row.checked = input.checked
                row.dispatchEvent(new Event('change', { bubbles: true }))
            }
        },
        commitPage(type) {
            for (const row of pendingRows) {
                if (!row.isConnected) continue
                row.dispatchEvent(
                    type === 'blur'
                        ? new Event('blur')
                        : new KeyboardEvent('keydown', { key: 'Enter' }),
                )
            }
            if (type === 'blur') pendingRows = []
        },
        measure(head) {
            this.widths[head.dataset.widthVar] = head.getBoundingClientRect().width + 'px'
        },
    }
}
