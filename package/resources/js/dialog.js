/*
 * Dialog and Sheet open and close natively through invoker commands. Two gaps remain:
 * closing on a backdrop click (Safari has no closedby="any" yet) and Livewire's
 * $this->dialog('name')->open() / ->close().
 */

export function initDialogs(win = window) {
    win.document.addEventListener('click', ({ target, clientX, clientY }) => {
        if (! target.matches?.('dialog[open]:is([data-slot="dialog-content"], [data-slot="sheet-content"])')) return

        // A click on the <dialog> itself outside its box landed on the backdrop.
        const box = target.getBoundingClientRect()

        if (clientX < box.left || clientX > box.right || clientY < box.top || clientY > box.bottom) target.close()
    })

    win.addEventListener('ui-dialog', ({ detail }) => {
        const dialog = win.document.getElementById(detail.name)

        if (! dialog) return

        if (detail.open) dialog.open || dialog.showModal()
        else dialog.close()
    })
}
