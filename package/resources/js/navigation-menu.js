/*
 * Navigation Menu triggers open their content popover on hover (after 50ms, like
 * Base UI) or click. Content opened by hover closes when the pointer leaves the
 * trigger and the content; content opened by click stays until dismissed.
 */

const DELAY = 50

export function initNavigationMenus(win = window) {
    const doc = win.document
    const timers = new Map()

    const contentOf = trigger => doc.getElementById(trigger.dataset.navigationMenu)
    const triggerOf = content => doc.querySelector(`[data-navigation-menu="${content.id}"]`)
    const isOpen = content => content.matches(':popover-open')

    const show = (content, by) => {
        clearTimeout(timers.get(content))
        content.dataset.openedBy = by
        if (! isOpen(content)) content.showPopover()
    }

    const hideLater = content => {
        clearTimeout(timers.get(content))
        timers.set(content, setTimeout(() => isOpen(content) && content.dataset.openedBy === 'hover' && content.hidePopover(), DELAY))
    }

    // The trigger mirrors its content's state.
    doc.addEventListener('toggle', ({ target, newState }) => {
        if (! target.matches?.('[data-slot="navigation-menu-content"]')) return

        triggerOf(target)?.setAttribute('aria-expanded', String(newState === 'open'))
    }, true)

    doc.addEventListener('click', ({ target }) => {
        const trigger = target.closest?.('[data-navigation-menu]')
        const content = trigger && contentOf(trigger)

        if (! content) return

        // A click on a menu the pointer just opened keeps it open.
        if (isOpen(content) && content.dataset.openedBy === 'hover') content.dataset.openedBy = 'click'
        else if (isOpen(content)) content.hidePopover()
        else show(content, 'click')
    })

    const parts = element => {
        const trigger = element?.closest?.('[data-navigation-menu]')
        const content = trigger ? contentOf(trigger) : element?.closest?.('[data-slot="navigation-menu-content"]')

        return content ? { trigger: trigger ?? triggerOf(content), content } : null
    }

    doc.addEventListener('pointerover', ({ target, relatedTarget, pointerType }) => {
        const found = parts(target)

        if (! found || pointerType === 'touch' || parts(relatedTarget)?.content === found.content) return

        clearTimeout(timers.get(found.content))

        if (isOpen(found.content)) return

        // Moving from one open menu to another trigger switches at once.
        const nav = found.trigger?.closest('[data-slot="navigation-menu"]')
        const switching = nav && [...nav.querySelectorAll('[data-slot="navigation-menu-content"]')].some(isOpen)

        timers.set(found.content, setTimeout(() => show(found.content, 'hover'), switching ? 0 : DELAY))
    })

    doc.addEventListener('pointerout', ({ target, relatedTarget }) => {
        const found = parts(target)

        if (found && parts(relatedTarget)?.content !== found.content) hideLater(found.content)
    })
}
