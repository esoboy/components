/*
 * Tooltips are manual popovers: shown while their trigger is hovered or focused.
 */

export function register() {
    const tooltipOf = trigger => document.getElementById(trigger.dataset.tooltip)

    const show = trigger => {
        const tooltip = tooltipOf(trigger)

        if (tooltip && ! tooltip.matches(':popover-open')) tooltip.showPopover()
    }

    const hide = trigger => {
        const tooltip = tooltipOf(trigger)

        if (tooltip?.matches(':popover-open')) tooltip.hidePopover()
    }

    // Moving between children of the trigger fires over/out pairs; only real exits hide.
    const leaves = (trigger, next) => ! trigger.contains(next)

    document.addEventListener('pointerover', ({ target }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger) show(trigger)
    })

    document.addEventListener('pointerout', ({ target, relatedTarget }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger && leaves(trigger, relatedTarget)) hide(trigger)
    })

    // Focus can come back to a trigger while a popover is hiding, when no popover may be
    // shown: after that, if it is still there.
    document.addEventListener('focusin', ({ target }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger) queueMicrotask(() => trigger.contains(document.activeElement) && show(trigger))
    })

    document.addEventListener('focusout', ({ target, relatedTarget }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger && leaves(trigger, relatedTarget)) hide(trigger)
    })

    document.addEventListener('keydown', ({ key }) => {
        if (key === 'Escape') document.querySelectorAll('[data-slot="tooltip-content"]:popover-open').forEach(tooltip => tooltip.hidePopover())
    })
}
