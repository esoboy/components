import { adjacentItem, enabled, setActiveOption } from '../behaviors/keyboard.js'
import { focusInPlace, popover } from '../behaviors/overlay.js'

export function searchInput(panel) {
    return panel.closest('[data-ui-overlay]').querySelector('input[role="combobox"]')
}

export function combobox() {
    let pendingArrow = null
    return Object.assign(popover(), {
        pointingToOption: false,
        choosing: false,
        editedQuery: false,
        finishBrowsing(event, paginated) {
            this.toggled(event)
            if (event.target !== this.panel) return
            if (event.newState === 'open') {
                this.choosing = false
            } else {
                if (
                    !this.choosing &&
                    (this.editedQuery ||
                        this.$wire.searching ||
                        (paginated && this.$wire.browsePage != null))
                )
                    this.$wire.finishSearch()
                this.editedQuery = false
            }
        },

        selectedOption(panel) {
            return enabled(panel, '[role="option"][aria-selected="true"]').at(-1)
        },
        focusOnOpen(panel, focus, focusLast, wasOpen) {
            if (!wasOpen) {
                pendingArrow = null
                this.highlightOption(this.selectedOption(panel))
            }
            if (focus) focusInPlace(searchInput(panel))
        },
        initializeOptions(list) {
            if (!this.opened) return
            if (pendingArrow || list.dataset.autoHighlight === 'true') {
                const options = enabled(list, '[role="option"]')
                this.highlightOption(options.at(pendingArrow === 'ArrowUp' ? -1 : 0))
            } else this.highlightOption(null)
            pendingArrow = null
        },
        highlightOption(option, pointer = false) {
            if (!this.panel || option?.matches(':disabled,[aria-disabled="true"]')) return
            this.pointingToOption = pointer
            if (!pointer) option?.scrollIntoView({ block: 'nearest' })
            setActiveOption(this.panel, searchInput(this.panel), option)
        },
        navigateChips(event) {
            const input = this.$root.querySelector('.ui-combobox-multiple input[role="combobox"]')
            if (!input || input.disabled || input.readOnly) return false
            const chips = [...this.$root.querySelectorAll('.ui-combobox-chip')]
            const current = chips.indexOf(event.target.closest('.ui-combobox-chip'))
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
                chips.at(-1)?.querySelector('button')?.click()
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
                focusInPlace(chips[current + 1] ?? chips[current - 1] ?? input)
                chips[current].querySelector('button')?.click()
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
                        pendingArrow = event.key === 'PageUp' ? 'ArrowUp' : 'ArrowDown'
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
            const current = options.find((item) => item.hasAttribute('data-highlighted'))
            if (event.key === 'Enter' && !current) {
                this.close()
                return
            }
            event.preventDefault()
            event.stopPropagation()
            if (event.key === 'Enter') {
                current.click()
                this.close(true)
                return
            }
            if (!wasOpen && current) return
            this.highlightOption(adjacentItem(options, current, event.key === 'ArrowDown'))
        },
    })
}
