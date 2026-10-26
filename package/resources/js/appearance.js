/*
 * Appearance: light, dark or system.
 *
 * The server stamps data-appearance on <html> from the ui_appearance cookie, so the
 * page already paints in the right scheme; this module only handles changes.
 */

const COOKIE = 'ui_appearance'
const CHANNEL = 'ui-appearance'
const VALUES = ['light', 'dark', 'system']

export function createAppearance(win = window) {
    const doc = win.document
    const root = doc.documentElement
    const media = win.matchMedia('(prefers-color-scheme: dark)')
    const motion = win.matchMedia('(prefers-reduced-motion: reduce)')
    const channel = win.BroadcastChannel ? new win.BroadcastChannel(CHANNEL) : null
    const listeners = new Set()

    let current = initial()
    let transition = null

    function initial() {
        const stamped = root.getAttribute('data-appearance')

        if (VALUES.includes(stamped)) return stamped

        // A cached or static page may lack the server stamp; fall back to the cookie.
        const cookie = doc.cookie.match(new RegExp(`(?:^|; )${COOKIE}=(light|dark)`))?.[1]

        if (cookie) root.setAttribute('data-appearance', cookie)

        return cookie ?? 'system'
    }

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

        // A page that stops rendering never captures the old state; skipping still commits.
        const stalled = win.setTimeout(() => transition?.skipTransition(), 1000)

        transition.updateCallbackDone.finally(() => win.clearTimeout(stalled))

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

    function change(value, { broadcast }) {
        if (! VALUES.includes(value) || value === current) return

        const changesScheme = resolve(value) !== resolve(current)

        current = value
        render(changesScheme)
        notify()

        if (broadcast) {
            persist(value)
            channel?.postMessage(value)
        }
    }

    // Other tabs already persisted the value; only mirror it here.
    const onMessage = ({ data }) => change(data, { broadcast: false })

    // The OS flipped while following the system: CSS already switched, keep state in sync.
    const onSystemChange = () => current === 'system' && notify()

    // Livewire: $this->dispatch('ui-set-appearance', value: 'dark')
    const onSetEvent = ({ detail }) => change(detail?.value ?? detail, { broadcast: true })

    channel?.addEventListener('message', onMessage)
    media.addEventListener('change', onSystemChange)
    win.addEventListener('ui-set-appearance', onSetEvent)

    return {
        get value() {
            return current
        },

        get resolved() {
            return resolve(current)
        },

        set(value) {
            change(value, { broadcast: true })
        },

        toggle() {
            this.set(this.resolved === 'dark' ? 'light' : 'dark')
        },

        subscribe(listener) {
            listeners.add(listener)

            return () => listeners.delete(listener)
        },

        destroy() {
            channel?.close()
            media.removeEventListener('change', onSystemChange)
            win.removeEventListener('ui-set-appearance', onSetEvent)
            listeners.clear()
        },
    }
}
