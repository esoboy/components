import { moveGroupFocus } from '../behaviors/keyboard.js'

export function tabs(readValue) {
    let owner, observer
    return {
        focused: null,
        init() {
            owner = this.$wire
            const root = this.$root
            observer = new MutationObserver((records) => {
                if (
                    records.some(
                        ({ target }) =>
                            target.matches('[role="tab"]:disabled') &&
                            target.closest('.ui-tabs') === root,
                    )
                ) {
                    owner.restrictTabs(
                        [...root.querySelectorAll('[role="tab"]:disabled')]
                            .filter((tab) => tab.closest('.ui-tabs') === root)
                            .map((tab) => JSON.parse(tab.dataset.uiNext)),
                    )
                }
            })
            observer.observe(root, {
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled'],
            })
        },
        destroy() {
            observer.disconnect()
        },
        get active() {
            return readValue()
        },
        navigate(event, activateOnFocus) {
            const tab = moveGroupFocus(event, '[role="tablist"]', '[role="tab"]')
            if (tab && activateOnFocus) tab.click()
        },
    }
}
