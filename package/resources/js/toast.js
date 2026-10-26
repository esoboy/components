/*
 * Toasts, styled after shadcn's Sonner. Livewire: $this->toast('Saved', type: 'success');
 * Blade: wire:click="$dispatch('ui-toast', { message: 'Saved' })".
 */

const VISIBLE = 3
const DURATION = 4000

const svg = paths => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`

const ICONS = {
    success: svg('<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>'),
    info: svg('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'),
    warning: svg('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>'),
    error: svg('<path d="m15 9-6 6"/><path d="M2.586 16.726A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2h6.624a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586z"/><path d="m9 9 6 6"/>'),
}

export function initToasts(win = window) {
    const doc = win.document
    const timers = new Map()
    let paused = false

    const element = (tag, slot, text) => {
        const node = doc.createElement(tag)
        node.dataset.slot = slot
        if (text !== undefined) node.textContent = text
        return node
    }

    const schedule = (toast, remaining) => {
        timers.set(toast, { remaining, started: Date.now(), id: paused ? null : win.setTimeout(() => dismiss(toast), remaining) })
    }

    const dismiss = toast => {
        win.clearTimeout(timers.get(toast)?.id)
        timers.delete(toast)
        toast.dataset.removed = ''
        win.setTimeout(() => {
            const toaster = toast.parentElement
            toast.remove()
            if (toaster && ! toaster.children.length && toaster.matches(':popover-open')) toaster.hidePopover()
        }, 200)
    }

    const show = ({ message, description = null, type = 'default', duration = null }) => {
        const toaster = doc.querySelector('[data-slot="toaster"]')

        if (! toaster || ! message) return

        const toast = element('li', 'toast')
        toast.dataset.type = type
        toast.setAttribute('role', 'status')

        if (ICONS[type]) toast.append(Object.assign(element('div', 'toast-icon'), { innerHTML: ICONS[type] }))

        const content = element('div', 'toast-content')
        content.append(element('div', 'toast-title', message))
        if (description) content.append(element('div', 'toast-description', description))
        toast.append(content)

        toaster.append(toast)

        // Keep toasts above a modal dialog opened after the toaster.
        if (toaster.matches(':popover-open') && doc.querySelector('dialog:modal')) toaster.hidePopover()
        if (! toaster.matches(':popover-open')) toaster.showPopover()

        ;[...toaster.querySelectorAll('[data-slot="toast"]:not([data-removed])')].slice(0, -VISIBLE).forEach(dismiss)

        schedule(toast, duration ?? DURATION)
    }

    // Hovering the toaster pauses every toast, like Sonner.
    doc.addEventListener('pointerover', ({ target }) => {
        if (paused || ! target.closest?.('[data-slot="toaster"]')) return

        paused = true
        timers.forEach(timer => {
            win.clearTimeout(timer.id)
            timer.remaining -= Date.now() - timer.started
        })
    })

    doc.addEventListener('pointerout', ({ target, relatedTarget }) => {
        const toaster = target.closest?.('[data-slot="toaster"]')

        if (! paused || ! toaster || toaster.contains(relatedTarget)) return

        paused = false
        timers.forEach((timer, toast) => schedule(toast, timer.remaining))
    })

    win.addEventListener('ui-toast', ({ detail }) => show(detail ?? {}))
}
