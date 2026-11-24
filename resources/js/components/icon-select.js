import { combobox, searchInput } from './combobox.js'

export function iconSelect() {
    let pendingSelection
    return Object.assign(combobox(), {
        browseChanged: false,
        chooseIcon(event) {
            const option = event.target.closest('button[role="option"]:enabled')
            if (!option) return
            this.close(true, false)
            return this.selectIcon(option.value)
        },
        editIconQuery(value) {
            this.browseChanged = true
            if (pendingSelection) pendingSelection.query = value
        },
        displayIcon(value, keepQuery = false) {
            const { iconCaption, iconField } = this.$refs
            if (iconCaption) iconCaption.value = value ?? iconCaption.dataset.placeholder
            else if (!keepQuery) searchInput(this.panel).value = value ?? ''
            if (iconField) {
                iconField.value = value ?? ''
                iconField.disabled = this.$wire.disabled || value === null
            }
        },
        async selectIcon(value) {
            const wire = this.$wire
            if (wire.disabled || wire.readonly) return
            this.browseChanged = false
            if (wire.mode === 'controlled') {
                return value === null ? wire.clearSelection() : wire.choose(value)
            }
            const root = wire.$el
            const selection = { value }
            pendingSelection = selection
            this.displayIcon(value)
            if (wire.mode === 'bound') wire.$set('value', value, false)
            try {
                await wire.commitIcon(value)
            } catch {
                if (
                    pendingSelection === selection &&
                    root.isConnected &&
                    wire.mode === 'bound' &&
                    wire.value === value
                ) {
                    wire.$set('value', root.dataset.confirmedIcon || null, false)
                }
            } finally {
                if (pendingSelection === selection) {
                    pendingSelection = null
                    if (
                        root.isConnected &&
                        this.opened &&
                        selection.query !== undefined &&
                        (!wire.searching || wire.query !== selection.query)
                    ) {
                        wire.$set('query', selection.query)
                    }
                }
                if (root.isConnected) {
                    this.displayIcon(
                        pendingSelection ? pendingSelection.value : wire.value,
                        this.opened,
                    )
                }
            }
        },
        finishBrowsing() {
            if (this.browseChanged) {
                if (pendingSelection) delete pendingSelection.query
                this.$wire.finishSearch()
            }
            this.browseChanged = false
        },
    })
}
