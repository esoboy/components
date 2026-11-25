import { moveGroupFocus } from '../behaviors/keyboard.js'
import { observeChildren } from '../behaviors/slot.js'

export function tabs() {
    let owner, disconnect
    return {
        get active() {
            return owner?.value ?? null
        },
        set active(value) {
            if (owner && owner.value !== value) owner.value = value
        },
        focused: null,
        init() {
            owner = this.$wire
            const root = this.$root
            const reconcile = (records) => {
                if (
                    records?.every(
                        ({ target }) => target.closest('.ui-tabs, [role="tabpanel"]') !== root,
                    )
                )
                    return
                const tabs = [...root.querySelectorAll('[role="tab"]')].filter(
                    (tab) => tab.closest('.ui-tabs') === root,
                )
                const panels = new Map(
                    [...root.querySelectorAll('[role="tabpanel"]')]
                        .filter((panel) => panel.closest('.ui-tabs') === root)
                        .map((panel) => [panel.dataset.value, panel]),
                )
                for (const tab of tabs) {
                    const panel = panels.get(tab.dataset.value)
                    if (panel) {
                        tab.setAttribute('aria-controls', panel.id)
                        panel.setAttribute('aria-labelledby', tab.id)
                    }
                }
                const values = tabs.filter((tab) => !tab.disabled).map((tab) => tab.dataset.value)
                if (!values.includes(this.active)) this.active = values[0] ?? null
                if (!values.includes(this.focused)) this.focused = null
            }
            reconcile()
            disconnect = observeChildren(root, reconcile, ['disabled', 'data-value', 'id'])
        },
        destroy() {
            disconnect()
        },
        navigate(event, activateOnFocus) {
            const tab = moveGroupFocus(event, '[role="tablist"]', '[role="tab"]')
            if (tab && activateOnFocus) this.active = tab.dataset.value
        },
    }
}
