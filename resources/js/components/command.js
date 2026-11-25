import { adjacentItem, setActiveOption } from '../behaviors/keyboard.js'

export function command() {
    let list, query, active
    return {
        chooseOnUpdate: false,
        init() {
            list = this.$root.querySelector('[role="listbox"]')
            query = this.$root.querySelector('input[type="search"]')
            const sync = () => this.sync()
            this.$nextTick(sync)
            this.$wire.$hook('morphed', sync)
        },
        sync() {
            if (list.dataset.query !== query.value) return
            if (!list.contains(active) || active.disabled)
                active =
                    list.querySelector('[aria-selected="true"]:enabled') ??
                    list.querySelector('[role="option"]:enabled')
            this.highlight(active)
            if (this.chooseOnUpdate) {
                this.chooseOnUpdate = false
                active?.click()
            }
        },
        highlight(option) {
            active = option
            setActiveOption(list, query, option)
        },
        navigate(event, forward) {
            if (event.isComposing) return
            event.preventDefault()
            if (forward === undefined) {
                this.chooseOnUpdate = true
                return this.sync()
            }
            const options = [...list.querySelectorAll('[role="option"]:enabled')]
            const next = adjacentItem(options, active, forward)
            this.highlight(next ?? null)
            next?.scrollIntoView({ block: 'nearest' })
        },
    }
}
