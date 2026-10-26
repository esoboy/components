/*
 * Tooltips are manual popovers: shown while their trigger is hovered or focused.
 */

export function initTooltips(win = window) {
    const doc = win.document

    const tooltipOf = trigger => doc.getElementById(trigger.dataset.tooltip)

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

    doc.addEventListener('pointerover', ({ target }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger) show(trigger)
    })

    doc.addEventListener('pointerout', ({ target, relatedTarget }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger && leaves(trigger, relatedTarget)) hide(trigger)
    })

    doc.addEventListener('focusin', ({ target }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger) show(trigger)
    })

    doc.addEventListener('focusout', ({ target, relatedTarget }) => {
        const trigger = target.closest?.('[data-tooltip]')

        if (trigger && leaves(trigger, relatedTarget)) hide(trigger)
    })

    doc.addEventListener('keydown', ({ key }) => {
        if (key === 'Escape') doc.querySelectorAll('[data-slot="tooltip-content"]:popover-open').forEach(tooltip => tooltip.hidePopover())
    })
}
