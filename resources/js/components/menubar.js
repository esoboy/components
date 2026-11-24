import { enabled, moveFocus } from '../behaviors/keyboard.js'

export function menubar() {
    return {
        navigate(event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return
            const menu = event.target.closest('[data-ui-overlay]')
            if (menu?.parentElement !== this.$root) return
            const triggers = enabled(
                this.$root,
                ':scope > [data-ui-overlay] > [data-ui-trigger] > *',
            )
            const panel = menu.querySelector('[popover]')
            const wasOpen = panel?.matches(':popover-open')
            const current = menu.querySelector('[data-ui-trigger]')?.firstElementChild
            if (!triggers.includes(current)) return
            if (wasOpen) panel.hidePopover()
            const trigger = moveFocus(event, triggers, false, current)
            event.stopPropagation()
            if (wasOpen)
                trigger?.dispatchEvent(
                    new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }),
                )
        },
    }
}
