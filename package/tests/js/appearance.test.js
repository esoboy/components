import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createAppearance } from '../../resources/js/appearance.js'

const root = document.documentElement

let instance
let system

function mockMedia({ dark = false, reducedMotion = false } = {}) {
    const listeners = new Set()
    const scheme = {
        matches: dark,
        addEventListener: (_, listener) => listeners.add(listener),
        removeEventListener: (_, listener) => listeners.delete(listener),
    }
    const motion = { matches: reducedMotion, addEventListener() {}, removeEventListener() {} }

    window.matchMedia = query => (query.includes('reduced-motion') ? motion : scheme)

    return {
        setDark(value) {
            scheme.matches = value
            listeners.forEach(listener => listener())
        },
    }
}

function mockViewTransition({ settle = true } = {}) {
    const transitions = []

    document.startViewTransition = vi.fn(update => {
        let finish
        const transition = {
            update,
            duringUpdate: null,
            finished: new Promise(resolve => (finish = resolve)),
            skipTransition: vi.fn(() => transition.run()),
            run() {
                transition.duringUpdate = root.hasAttribute('data-appearance-transition')
                update()
                finish()
            },
        }

        transitions.push(transition)

        if (settle) transition.run()

        return transition
    })

    return transitions
}

function cookie() {
    return document.cookie.match(/(?:^|; )ui_appearance=(\w+)/)?.[1] ?? null
}

function create() {
    instance = createAppearance(window)

    return instance
}

beforeEach(() => {
    root.removeAttribute('data-appearance')
    root.removeAttribute('data-appearance-transition')
    document.cookie = 'ui_appearance=; path=/; max-age=0'
    delete document.startViewTransition
    Object.defineProperty(document, 'visibilityState', { value: 'visible', configurable: true })
    system = mockMedia()
})

afterEach(() => {
    instance?.destroy()
    instance = null
})

describe('initial state', () => {
    it('follows the system when nothing was chosen', () => {
        system = mockMedia({ dark: true })

        const appearance = create()

        expect(appearance.value).toBe('system')
        expect(appearance.resolved).toBe('dark')
        expect(root.hasAttribute('data-appearance')).toBe(false)
    })

    it('trusts the attribute the server stamped on <html>', () => {
        root.setAttribute('data-appearance', 'dark')

        expect(create().value).toBe('dark')
    })
})

describe('changing the appearance', () => {
    it('applies, persists and announces a forced scheme', () => {
        const appearance = create()
        const subscriber = vi.fn()
        const announced = vi.fn()

        appearance.subscribe(subscriber)
        window.addEventListener('ui-appearance-changed', announced)

        appearance.set('dark')

        expect(root.getAttribute('data-appearance')).toBe('dark')
        expect(cookie()).toBe('dark')
        expect(subscriber).toHaveBeenCalledWith({ value: 'dark', resolved: 'dark' })
        expect(announced.mock.calls[0][0].detail).toEqual({ value: 'dark', resolved: 'dark' })

        window.removeEventListener('ui-appearance-changed', announced)
    })

    it('forgets the choice when going back to system', () => {
        document.cookie = 'ui_appearance=dark; path=/'
        root.setAttribute('data-appearance', 'dark')
        const appearance = create()

        appearance.set('system')

        expect(root.hasAttribute('data-appearance')).toBe(false)
        expect(cookie()).toBeNull()
    })

    it('ignores unknown values and repeats', () => {
        const appearance = create()
        const subscriber = vi.fn()

        appearance.subscribe(subscriber)
        appearance.set('sepia')
        appearance.set('system')

        expect(appearance.value).toBe('system')
        expect(subscriber).not.toHaveBeenCalled()
    })

    it('toggles against what is on screen', () => {
        system = mockMedia({ dark: true })
        const appearance = create()

        appearance.toggle()

        expect(appearance.value).toBe('light')
    })

    it('accepts commands dispatched from Livewire', () => {
        const appearance = create()

        window.dispatchEvent(new CustomEvent('ui-set-appearance', { detail: { value: 'dark' } }))

        expect(appearance.value).toBe('dark')
        expect(cookie()).toBe('dark')
    })

    it('announces when the system flips while following it', () => {
        const appearance = create()
        const subscriber = vi.fn()

        appearance.subscribe(subscriber)
        system.setDark(true)

        expect(subscriber).toHaveBeenCalledWith({ value: 'system', resolved: 'dark' })
    })

    it('stops listening once destroyed', () => {
        const appearance = create()

        appearance.destroy()
        window.dispatchEvent(new CustomEvent('ui-set-appearance', { detail: { value: 'dark' } }))

        expect(appearance.value).toBe('system')
    })
})

describe('transition', () => {
    it('crossfades only when the visible scheme changes', () => {
        const transitions = mockViewTransition()
        const appearance = create()

        appearance.set('light')
        expect(transitions).toHaveLength(0)

        appearance.set('dark')
        expect(transitions).toHaveLength(1)
        expect(transitions[0].duringUpdate).toBe(true)
    })

    it('clears the transition marker when the crossfade ends', async () => {
        const transitions = mockViewTransition()

        create().set('dark')
        await transitions[0].finished
        await Promise.resolve()

        expect(root.hasAttribute('data-appearance-transition')).toBe(false)
    })

    it('switches instantly for reduced motion', () => {
        const transitions = mockViewTransition()
        system = mockMedia({ reducedMotion: true })

        create().set('dark')

        expect(transitions).toHaveLength(0)
        expect(root.getAttribute('data-appearance')).toBe('dark')
    })

    it('cuts a running crossfade short on rapid switching', () => {
        const transitions = mockViewTransition({ settle: false })
        const appearance = create()

        appearance.set('dark')
        appearance.set('light')

        expect(transitions).toHaveLength(1)
        expect(transitions[0].skipTransition).toHaveBeenCalled()
        expect(root.getAttribute('data-appearance')).toBe('light')
    })
})
