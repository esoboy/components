import { popover } from '../behaviors/overlay.js'

export function select(source, initial) {
    return Object.assign(popover(), {
        source,
        initial,
        syncField(value) {
            const choices = new Set(Object.values(this.$wire.options).map((option) => option.value))
            const valid = (value) => [value].flat().filter((id) => id != null && choices.has(id))
            const selected = valid(value)
            const defaults = source === 'value' ? valid(initial) : selected
            // Separate options preserve both submission orders, even immediately after form.reset().
            this.$refs.field.replaceChildren(
                ...defaults.map((id) => new Option('', id, true, false)),
                ...selected.map((id) => new Option('', id, false, true)),
            )
            return this.$wire.multiple ? selected : (selected[0] ?? null)
        },
        commitSelection(value) {
            const wire = this.$wire
            value = this.syncField(value)
            if (source === 'value') wire.$set(source, value)
            else this.syncField(wire[source])
            wire.$dispatch('value-changed', { value })
        },
        chooseOption(value) {
            const wire = this.$wire
            if (wire.disabled || wire.readonly) return
            if (wire.multiple) {
                const selected = wire[source] ?? []
                value = selected.includes(value)
                    ? selected.filter((id) => id !== value)
                    : [...selected, value]
            } else this.close(true)
            this.commitSelection(value)
        },
    })
}
