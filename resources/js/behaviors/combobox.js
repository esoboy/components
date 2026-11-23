import { enabled } from './navigation.js'
import { focusInPlace, popover } from './overlay.js'

function searchInput(panel) {
    return panel.closest('[data-ui-overlay]').querySelector('input[role="combobox"]')
}

export function combobox() {
    let pendingSelection
    return Object.assign(popover(), {
        pointingToOption: false,
        pendingArrow: null,
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
        selectedOption(panel) {
            return enabled(panel, '[role="option"][aria-selected="true"]').at(-1)
        },
        focusOnOpen(panel, focus, focusLast, wasOpen) {
            if (!wasOpen) {
                this.pendingArrow = null
                this.highlightOption(this.selectedOption(panel))
            }
            if (focus) focusInPlace(searchInput(panel))
        },
        initializeOptions(list) {
            if (!this.opened) return
            if (this.pendingArrow || list.dataset.autoHighlight === 'true') {
                const options = enabled(list, '[role="option"]')
                this.highlightOption(options.at(this.pendingArrow === 'ArrowUp' ? -1 : 0))
            } else this.highlightOption(null)
            this.pendingArrow = null
        },
        highlightOption(option, pointer = false) {
            if (!this.panel || option?.matches(':disabled,[aria-disabled="true"]')) return
            this.pointingToOption = pointer
            if (!pointer) option?.scrollIntoView({ block: 'nearest' })
            const previous = this.panel.querySelector('[role="option"][data-highlighted]')
            if (previous !== option) {
                previous?.removeAttribute('data-highlighted')
                option?.setAttribute('data-highlighted', '')
            }
            const input = searchInput(this.panel)
            if (option?.id) input?.setAttribute('aria-activedescendant', option.id)
            else input?.removeAttribute('aria-activedescendant')
        },
        navigateChips(event) {
            const input = this.$root.querySelector('.ui-combobox-multiple input[role="combobox"]')
            if (!input || input.disabled || input.readOnly) return false
            const chips = [...this.$root.querySelectorAll('[data-slot="combobox-chip"]')]
            const current = chips.indexOf(event.target.closest('[data-slot="combobox-chip"]'))
            const previous =
                getComputedStyle(input).direction === 'rtl' ? 'ArrowRight' : 'ArrowLeft'
            const next = previous === 'ArrowLeft' ? 'ArrowRight' : 'ArrowLeft'
            if (
                current === -1 &&
                event.key === 'Backspace' &&
                event.target === input &&
                input.value === '' &&
                chips.length
            ) {
                event.preventDefault()
                this.highlightOption(null)
                this.$wire.removeLast()
                return true
            }
            let destination
            if (current === -1) {
                if (
                    event.key !== previous ||
                    event.target !== input ||
                    input.selectionStart !== 0 ||
                    !chips.length
                )
                    return false
                destination = chips.length - 1
            } else if (['Backspace', 'Delete'].includes(event.key)) {
                event.preventDefault()
                event.stopPropagation()
                this.highlightOption(null)
                const index = Math.min(current, chips.length - 2)
                this.$wire.choose(JSON.parse(chips[current].dataset.value)).then(() => {
                    const remaining = this.$root.querySelectorAll('[data-slot="combobox-chip"]')
                    focusInPlace(remaining[index] ?? input)
                })
                return true
            } else if (event.key === previous) destination = current - 1
            else if (event.key === next) destination = current + 1
            else if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
                focusInPlace(input)
                this.open(false)
                destination = -1
            } else if (['Enter', ' '].includes(event.key) && event.target === chips[current])
                destination = -1
            else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                focusInPlace(input)
                return false
            } else return false
            event.preventDefault()
            event.stopPropagation()
            focusInPlace(chips[destination] ?? input)
            return true
        },
        navigate(event) {
            if (event.defaultPrevented) return
            const panel = this.panel
            if (this.navigateChips(event)) return
            if (this.opened && ['PageUp', 'PageDown'].includes(event.key)) {
                const button = panel.querySelector(`[aria-keyshortcuts="${event.key}"]`)
                if (button) {
                    event.preventDefault()
                    if (!button.disabled) {
                        this.pendingArrow = event.key === 'PageUp' ? 'ArrowUp' : 'ArrowDown'
                        button.click()
                    }
                }
                return
            }
            if (event.key === 'Tab') {
                this.close()
                return
            }
            if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return
            const wasOpen = this.opened
            if (!wasOpen && event.key === 'Enter') return
            if (!wasOpen) this.open(false)
            const options = enabled(panel, '[role="option"]')
            const current = options.findIndex((item) => item.hasAttribute('data-highlighted'))
            if (event.key === 'Enter' && current === -1) {
                this.close()
                return
            }
            event.preventDefault()
            event.stopPropagation()
            if (event.key === 'Enter') {
                options[current].click()
                this.close(true)
                return
            }
            if (!wasOpen && current !== -1) return
            const index =
                event.key === 'ArrowDown'
                    ? (current + 1) % options.length
                    : current === -1
                      ? options.length - 1
                      : (current - 1 + options.length) % options.length
            this.highlightOption(options[index] ?? null)
        },
    })
}
