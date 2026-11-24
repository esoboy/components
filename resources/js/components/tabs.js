import { enabled, moveFocus } from '../behaviors/keyboard.js'

export function tabs(initial = null, orientation = 'horizontal') {
    return {
        active: initial === null ? null : String(initial),
        init() {
            this.active ??=
                this.$root.querySelector('[role="tab"]:not([disabled])')?.dataset.value ?? null
        },
        navigate(event) {
            const nodes = enabled(event.currentTarget, '[role="tab"]')
            const target = moveFocus(event, nodes, orientation === 'vertical')
            if (target) this.active = target.dataset.value
        },
    }
}
