/*
 * Clicking an Input Group addon (outside its buttons) focuses the group's input.
 */

export function initInputGroups(win = window) {
    win.document.addEventListener('click', ({ target }) => {
        const addon = target.closest?.('[data-slot="input-group-addon"]')

        if (! addon || target.closest('button')) return

        addon.parentElement.querySelector('[data-slot="input-group-control"]')?.focus()
    })
}
