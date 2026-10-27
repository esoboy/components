/*
 * Hover cards are manual popovers opened by hovering or focusing their trigger, after
 * its data-delay, and closed data-close-delay after the pointer leaves both the
 * trigger and the card, so the pointer can travel into the card.
 */

export function register() {
    const timers = new Map()

    const cardOf = trigger => document.getElementById(trigger.dataset.hoverCard)
    const triggerOf = card => document.querySelector(`[data-hover-card="${card.id}"]`)

    const schedule = (card, open, delay) => {
        clearTimeout(timers.get(card))
        timers.set(card, setTimeout(() => {
            if (open !== card.matches(':popover-open')) open ? card.showPopover() : card.hidePopover()
        }, delay))
    }

    const enter = trigger => {
        const card = cardOf(trigger)

        if (card) schedule(card, true, Number(trigger.dataset.delay ?? 600))
    }

    const leave = (trigger, card) => {
        if (card) schedule(card, false, Number(trigger?.dataset.closeDelay ?? 300))
    }

    // Moving between children fires over/out pairs; only real exits count.
    const target = element => element?.closest?.('[data-hover-card], [data-slot="hover-card-content"]')

    document.addEventListener('pointerover', ({ target: element, relatedTarget }) => {
        const node = target(element)

        if (! node || node.contains(relatedTarget)) return

        if (node.dataset.hoverCard) enter(node)
        else schedule(node, true, 0)
    })

    document.addEventListener('pointerout', ({ target: element, relatedTarget }) => {
        const node = target(element)

        if (! node || node.contains(relatedTarget)) return

        node.dataset.hoverCard ? leave(node, cardOf(node)) : leave(triggerOf(node), node)
    })

    document.addEventListener('focusin', ({ target: element }) => {
        const trigger = element.closest?.('[data-hover-card]')

        if (trigger) enter(trigger)
    })

    document.addEventListener('focusout', ({ target: element, relatedTarget }) => {
        const node = target(element)

        if (node && ! node.contains(relatedTarget) && ! target(relatedTarget)) {
            node.dataset.hoverCard ? leave(node, cardOf(node)) : leave(triggerOf(node), node)
        }
    })

    document.addEventListener('keydown', ({ key }) => {
        if (key === 'Escape') document.querySelectorAll('[data-slot="hover-card-content"]:popover-open').forEach(card => card.hidePopover())
    })
}
