/*
 * Appearance: light, dark or system.
 *
 * The server stamps data-appearance on <html> from the ui_appearance cookie, so the
 * page already paints in the right scheme; this module only handles changes.
 */

const COOKIE = 'ui_appearance'
const VALUES = ['light', 'dark', 'system']

export function createAppearance(win = window) {
    const doc = win.document
    const root = doc.documentElement
    const media = win.matchMedia('(prefers-color-scheme: dark)')
    const motion = win.matchMedia('(prefers-reduced-motion: reduce)')
    const listeners = new Set()

    let current = VALUES.includes(root.getAttribute('data-appearance')) ? root.getAttribute('data-appearance') : 'system'
    let transition = null

    function resolve(value) {
        return value === 'system' ? (media.matches ? 'dark' : 'light') : value
    }

    function persist(value) {
        doc.cookie = value === 'system'
            ? `${COOKIE}=; path=/; max-age=0; samesite=lax`
            : `${COOKIE}=${value}; path=/; max-age=31536000; samesite=lax`
    }

    // Always writes the latest value, so a late view transition callback can't apply a stale one.
    function commit() {
        if (current === 'system') root.removeAttribute('data-appearance')
        else root.setAttribute('data-appearance', current)
    }

    function render(animate) {
        // Rapid switching: cut the running crossfade short and apply instantly.
        if (transition) {
            transition.skipTransition()

            return commit()
        }

        if (! animate || ! doc.startViewTransition || motion.matches || doc.visibilityState !== 'visible') {
            return commit()
        }

        root.setAttribute('data-appearance-transition', '')

        transition = doc.startViewTransition(commit)

        transition.finished.finally(() => {
            transition = null
            root.removeAttribute('data-appearance-transition')
        })
    }

    function notify() {
        const detail = { value: current, resolved: resolve(current) }

        listeners.forEach(listener => listener(detail))

        // Livewire components can react with #[On('ui-appearance-changed')].
        win.dispatchEvent(new win.CustomEvent('ui-appearance-changed', { detail }))
    }

    function set(value) {
        if (! VALUES.includes(value) || value === current) return

        const changesScheme = resolve(value) !== resolve(current)

        current = value
        persist(value)
        render(changesScheme)
        notify()
    }

    // The OS flipped while following the system: CSS already switched, keep state in sync.
    const onSystemChange = () => current === 'system' && notify()

    // Livewire: $this->dispatch('ui-set-appearance', value: 'dark')
    const onSetEvent = ({ detail }) => set(detail?.value ?? detail)

    media.addEventListener('change', onSystemChange)
    win.addEventListener('ui-set-appearance', onSetEvent)

    return {
        get value() {
            return current
        },

        get resolved() {
            return resolve(current)
        },

        set,

        toggle() {
            set(resolve(current) === 'dark' ? 'light' : 'dark')
        },

        subscribe(listener) {
            listeners.add(listener)

            return () => listeners.delete(listener)
        },

        destroy() {
            media.removeEventListener('change', onSystemChange)
            win.removeEventListener('ui-set-appearance', onSetEvent)
            listeners.clear()
        },
    }
}
