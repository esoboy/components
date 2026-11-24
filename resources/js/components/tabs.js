import { enabled, moveFocus } from '../behaviors/keyboard.js'
import { observeChildren } from '../behaviors/slot.js'

export function tabs(initial = null, orientation = 'horizontal') {
    let disconnect
    return {
        active: initial === null ? null : String(initial),
        init() {
            const reconcile = () => {
                const values = [...this.$root.querySelectorAll('[role="tab"]:enabled')]
                    .filter((tab) => tab.closest('.ui-tabs') === this.$root)
                    .map((tab) => tab.dataset.value)
                if (!values.includes(this.active)) this.active = values[0] ?? null
            }
            reconcile()
            disconnect = observeChildren(this.$root, reconcile, ['disabled', 'data-value'])
        },
        destroy() {
            disconnect()
        },
        navigate(event) {
            const nodes = enabled(event.currentTarget, '[role="tab"]')
            const target = moveFocus(event, nodes, orientation === 'vertical')
            if (target) this.active = target.dataset.value
        },
    }
}
