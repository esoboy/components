/*
 * Toasts, as shadcn's Toast on Base UI: a stack with the newest in front and older ones
 * peeking behind, spread out while hovered or focused. Each closes after its timeout
 * (paused while the stack is spread or the window is in the background), by its close
 * button, or by a swipe down or right. Past the limit, the oldest leave.
 *
 * Livewire: Ui::toast('Saved', type: 'success').
 * Blade: wire:click="$dispatch('ui-toast', { message: 'Saved' })".
 * A toast with the id of a shown one updates it in place (e.g. loading, then success).
 */

import { readIcons } from '../support/icons.js'

const SWIPE_THRESHOLD = 40
const LEAVE_DURATION = 500

// Types with an icon; the toaster's view picks each one.
const TYPES = ['success', 'info', 'warning', 'error', 'loading']

let ids = 0

export function register() {
    const timers = new Map()
    let expanded = false

    const viewport = () => document.querySelector('[data-slot="toast-viewport"]')
    const live = () => [...(viewport()?.querySelectorAll(':scope > [data-slot="toast"]:not([data-ending-style])') ?? [])].reverse()

    const element = (tag, attributes = {}, text) => {
        const node = document.createElement(tag)

        for (const [name, value] of Object.entries(attributes)) node.setAttribute(name, value)
        if (text !== undefined) node.textContent = text

        return node
    }

    const button = (variant, size, attributes) => element('button', { type: 'button', 'data-slot': 'button', 'data-variant': variant, 'data-size': size, ...attributes })

    // --- timers: paused while spread out or while the page is in the background ---

    const paused = () => expanded || document.hidden || ! document.hasFocus()

    const schedule = (toast, remaining) => {
        window.clearTimeout(timers.get(toast)?.id)

        if (! remaining) return timers.delete(toast)

        timers.set(toast, { remaining, started: Date.now(), id: paused() ? null : window.setTimeout(() => close(toast), remaining) })
    }

    const pause = () => timers.forEach(timer => {
        if (timer.id === null) return

        window.clearTimeout(timer.id)
        timer.id = null
        timer.remaining -= Date.now() - timer.started
    })

    const resume = () => {
        if (! paused()) timers.forEach((timer, toast) => timer.id === null && schedule(toast, timer.remaining))
    }

    // --- layout: the CSS stacks toasts from these variables ---

    const layout = () => {
        const toasts = live()
        const limit = Number(viewport()?.dataset.limit ?? 3)
        let offset = 0

        toasts.forEach((toast, index) => {
            // Natural height, measured without the stack's fixed height.
            toast.style.height = 'auto'
            const height = toast.offsetHeight
            toast.style.height = ''

            toast.style.setProperty('--toast-index', index)
            toast.style.setProperty('--toast-offset-y', `${offset}px`)
            toast.style.setProperty('--toast-height', `${height}px`)
            toast.toggleAttribute('data-expanded', expanded)
            toast.firstElementChild?.toggleAttribute('data-expanded', expanded)
            toast.firstElementChild?.toggleAttribute('data-behind', index > 0 && ! expanded)

            if (index === 0) viewport().style.setProperty('--toast-frontmost-height', `${height}px`)
            if (index >= limit) close(toast, { limited: true })

            offset += height
        })
    }

    // --- one toast ---

    const fill = (toast, { message, description = null, type = 'default', action = null }) => {
        const id = toast.dataset.id
        const content = element('div', { 'data-slot': 'toast-content' })
        const text = element('div')
        const icons = readIcons(viewport())

        type === 'default' ? toast.removeAttribute('data-type') : toast.setAttribute('data-type', type)
        toast.setAttribute('role', type === 'error' ? 'alertdialog' : 'dialog')

        if (TYPES.includes(type) && icons[type]) content.append(Object.assign(element('span', { 'data-slot': 'toast-icon' }), { innerHTML: icons[type] }))

        if (message) text.append(element('h2', { 'data-slot': 'toast-title', id: `${id}-title` }, message))
        if (description) text.append(element('p', { 'data-slot': 'toast-description', id: `${id}-description` }, description))
        content.append(text)

        message ? toast.setAttribute('aria-labelledby', `${id}-title`) : toast.removeAttribute('aria-labelledby')
        description ? toast.setAttribute('aria-describedby', `${id}-description`) : toast.removeAttribute('aria-describedby')

        if (action?.label) {
            const run = button('outline', 'sm', { 'data-toast-action': '' })

            run.textContent = action.label
            run.addEventListener('click', () => {
                if (action.component && action.method) window.Livewire?.find(action.component)?.call(action.method, ...(action.params ?? []))
                close(toast)
            })
            content.append(run)
        }

        const dismiss = button('ghost', 'icon-sm', { 'data-toast-close': '', 'aria-label': viewport().dataset.closeLabel ?? '' })

        dismiss.innerHTML = icons.close ?? ''
        dismiss.addEventListener('click', () => close(toast))
        content.append(dismiss)

        toast.replaceChildren(content)
    }

    const show = detail => {
        const root = viewport()

        if (! root || ! (detail.message || detail.description)) return

        const existing = detail.id && live().find(toast => toast.dataset.id === String(detail.id))
        const toast = existing || element('div', { 'data-slot': 'toast', 'aria-modal': 'false', tabindex: '0', 'data-id': detail.id || `ui-toast-${++ids}` })

        fill(toast, detail)

        if (! existing) {
            for (const name of ['--toast-swipe-movement-x', '--toast-swipe-movement-y']) toast.style.setProperty(name, '0px')

            root.append(toast)
        }

        // Keep toasts above a modal dialog opened after the viewport.
        if (root.matches(':popover-open') && document.querySelector('dialog:modal')) root.hidePopover()
        if (! root.matches(':popover-open')) root.showPopover()

        const duration = detail.duration ?? (detail.type === 'loading' ? 0 : Number(root.dataset.timeout ?? 5000))

        schedule(toast, duration)
        layout()
    }

    const close = (toast, { limited = false, swipe = null } = {}) => {
        if (toast.hasAttribute('data-ending-style')) return

        window.clearTimeout(timers.get(toast)?.id)
        timers.delete(toast)

        if (limited) toast.setAttribute('data-limited', '')
        if (swipe) toast.setAttribute('data-swipe-direction', swipe)
        toast.setAttribute('data-ending-style', '')

        layout()

        window.setTimeout(() => {
            const root = toast.parentElement

            toast.remove()

            // The viewport also holds its icon templates.
            if (root && ! root.querySelector(':scope > [data-slot="toast"]')) {
                expanded = false
                if (root.matches(':popover-open')) root.hidePopover()
            }
        }, LEAVE_DURATION)
    }

    // --- spreading out on hover and focus ---

    const setExpanded = value => {
        if (expanded === value) return

        expanded = value
        value ? pause() : resume()
        layout()
    }

    document.addEventListener('pointerover', ({ target }) => target.closest?.('[data-slot="toast-viewport"]') && setExpanded(true))

    document.addEventListener('pointerout', ({ target, relatedTarget }) => {
        const root = target.closest?.('[data-slot="toast-viewport"]')

        if (root && ! root.contains(relatedTarget) && ! root.contains(document.activeElement)) setExpanded(false)
    })

    document.addEventListener('focusin', ({ target }) => target.closest?.('[data-slot="toast-viewport"]') && setExpanded(true))

    document.addEventListener('focusout', ({ target, relatedTarget }) => {
        const root = target.closest?.('[data-slot="toast-viewport"]')

        if (root && ! root.contains(relatedTarget) && ! root.matches(':hover')) setExpanded(false)
    })

    // F6 moves focus to the newest toast, as in Base UI.
    document.addEventListener('keydown', event => {
        if (event.key === 'F6' && live()[0]) {
            event.preventDefault()
            live()[0].focus()
        }

        if (event.key === 'Escape' && event.target.closest?.('[data-slot="toast"]')) close(event.target.closest('[data-slot="toast"]'))
    })

    window.addEventListener('blur', pause)
    window.addEventListener('focus', resume)
    document.addEventListener('visibilitychange', () => (document.hidden ? pause() : resume()))

    // --- swiping down or right dismisses ---

    document.addEventListener('pointerdown', event => {
        const toast = event.target.closest?.('[data-slot="toast"]')

        if (! toast || event.button !== 0 || event.target.closest('button, a, input')) return

        const start = { x: event.clientX, y: event.clientY }
        let movement = { x: 0, y: 0 }

        // Down and right follow the pointer; up and left resist.
        const damp = value => (value > 0 ? value : value / 8)

        const move = e => {
            movement = { x: damp(e.clientX - start.x), y: damp(e.clientY - start.y) }
            toast.style.setProperty('--toast-swipe-movement-x', `${movement.x}px`)
            toast.style.setProperty('--toast-swipe-movement-y', `${movement.y}px`)
        }

        const end = () => {
            window.removeEventListener('pointermove', move)
            window.removeEventListener('pointerup', end)
            window.removeEventListener('pointercancel', end)

            const direction = movement.y > SWIPE_THRESHOLD ? 'down' : movement.x > SWIPE_THRESHOLD ? 'right' : null

            if (direction) return close(toast, { swipe: direction })

            toast.style.setProperty('--toast-swipe-movement-x', '0px')
            toast.style.setProperty('--toast-swipe-movement-y', '0px')
        }

        window.addEventListener('pointermove', move)
        window.addEventListener('pointerup', end)
        window.addEventListener('pointercancel', end)
    })

    window.addEventListener('ui-toast', ({ detail }) => show(detail ?? {}))
}
