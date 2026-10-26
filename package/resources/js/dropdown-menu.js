/*
 * Dropdown menus open, close and dismiss natively as popovers. This adds what menus
 * need on top: arrow-key navigation, hover highlighting and submenus.
 */

const MENU = '[data-slot="dropdown-menu-content"], [data-slot="dropdown-menu-sub-content"]'
const ITEM = '[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]'
const ROW = '[data-slot="dropdown-menu-item"], [data-slot="dropdown-menu-checkbox-item"], [data-slot="dropdown-menu-radio-item"], [data-slot="dropdown-menu-sub-trigger"]'

export function initDropdownMenus(win = window) {
    const doc = win.document

    const itemsOf = menu => [...menu.querySelectorAll(ITEM)]
        .filter(item => item.closest('[role="menu"]') === menu && ! item.disabled && item.getAttribute('aria-disabled') !== 'true')

    const submenuOf = trigger => doc.getElementById(trigger.getAttribute('commandfor'))

    const open = popover => popover && ! popover.matches(':popover-open') && popover.showPopover()

    const close = popover => popover?.matches(':popover-open') && popover.hidePopover()

    // A freshly opened menu takes focus so arrow keys work right away.
    doc.addEventListener('toggle', ({ target, newState }) => {
        if (newState === 'open' && target.matches?.('[data-slot="dropdown-menu-content"]') && ! target.contains(doc.activeElement)) {
            target.focus()
        }
    }, true)

    doc.addEventListener('keydown', event => {
        const menu = event.target.closest?.(MENU)

        if (! menu) return

        const items = itemsOf(menu)
        const index = items.indexOf(event.target)

        const focus = position => {
            event.preventDefault()
            items.at(position)?.focus()
        }

        switch (event.key) {
            case 'ArrowDown': return focus(index < 0 ? 0 : (index + 1) % items.length)
            case 'ArrowUp': return focus(index < 0 ? -1 : (index - 1) % items.length)
            case 'Home': return focus(0)
            case 'End': return focus(-1)

            case 'ArrowRight':
                if (event.target.matches('[data-slot="dropdown-menu-sub-trigger"]')) {
                    event.preventDefault()
                    const submenu = submenuOf(event.target)
                    open(submenu)
                    itemsOf(submenu)[0]?.focus()
                }
                return

            case 'ArrowLeft':
                if (menu.matches('[data-slot="dropdown-menu-sub-content"]')) {
                    event.preventDefault()
                    close(menu)
                    doc.querySelector(`[commandfor="${menu.id}"]`)?.focus()
                }
        }
    })

    // The highlight follows the pointer, and hovering a sub trigger opens its submenu.
    doc.addEventListener('pointerover', ({ target }) => {
        const row = target.closest?.(ROW)
        const menu = row?.closest('[role="menu"]')

        if (! menu) return

        ;(row.matches(ITEM) ? row : row.querySelector(ITEM))?.focus({ preventScroll: true })

        for (const trigger of menu.querySelectorAll('[data-slot="dropdown-menu-sub-trigger"]')) {
            if (trigger.closest('[role="menu"]') !== menu) continue

            trigger === row ? open(submenuOf(trigger)) : close(submenuOf(trigger))
        }
    })
}
