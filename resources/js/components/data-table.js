export function dataTable() {
    return {
        widths: {},
        measure(head) {
            this.widths[head.dataset.widthVar] = head.getBoundingClientRect().width + 'px'
        },
    }
}

export function dataTableColumns() {
    return {
        keys() {
            return Array.from(
                this.$root.querySelectorAll('[data-movable]'),
                (row) => row.dataset.key,
            )
        },
        save(order) {
            return this.$wire.$set(this.$root.dataset.model + '.order', order)
        },
        async move(handle, step) {
            const order = this.keys()
            const key = handle.closest('[data-key]').dataset.key
            const from = order.indexOf(key)
            const to = from + step
            if (from < 0 || to < 0 || to >= order.length) return
            order.splice(to, 0, order.splice(from, 1)[0])
            await this.save(order)
            if (document.activeElement === document.body) {
                this.$root
                    .querySelector(`[data-key="${CSS.escape(key)}"] [data-column-handle]`)
                    ?.focus()
            }
        },
        applyVisibility({ target }) {
            const path = this.$root.dataset.model + '.hidden'
            const saved = this.$wire.$get(path)
            const hidden = new Set(saved && typeof saved === 'object' ? Object.values(saved) : [])
            if (target.checked) hidden.delete(target.value)
            else hidden.add(target.value)

            const boxes = [...this.$root.querySelectorAll('input[type=checkbox]')]
            const fields = boxes.filter((box) => box.hasAttribute('data-business'))
            // Blade disables fixed columns and the last visible field in the resolved layout.
            const isHidden = (box) => !box.disabled && hidden.has(box.value)
            if ((fields.length ? fields : boxes).every(isHidden)) {
                target.checked = true
                return
            }
            this.$wire.$set(
                path,
                boxes.filter(isHidden).map((box) => box.value),
            )
        },
        get sortConfig() {
            return {
                draggable: '[data-movable]',
                handle: '[data-column-handle]',
                onMove: (event) => event.related.hasAttribute('data-movable'),
            }
        },
    }
}
