import { afterEach, beforeAll, beforeEach, expect, it, vi } from 'vitest'
import { register as registerToast } from '../../resources/js/components/toast.js'

let viewport

beforeAll(() => {
    document.hasFocus = () => true
    registerToast()
})

beforeEach(() => {
    vi.useFakeTimers()
    document.body.innerHTML = '<div data-slot="toast-viewport" popover="manual" data-limit="3" data-timeout="5000" data-close-label="Close toast"></div>'
    viewport = document.querySelector('[data-slot="toast-viewport"]')
})

afterEach(() => {
    vi.runAllTimers()
    vi.useRealTimers()
})

const toast = detail => window.dispatchEvent(new CustomEvent('ui-toast', { detail }))
const live = () => [...viewport.querySelectorAll('[data-slot="toast"]:not([data-ending-style])')]
const newest = () => live().at(-1)

it('shows a title and description, with a close button', () => {
    toast({ message: 'Event created', description: 'Sunday at 9:00' })

    const item = newest()

    expect(viewport.matches(':popover-open')).toBe(true)
    expect(item.getAttribute('role')).toBe('dialog')
    expect(item.hasAttribute('data-type')).toBe(false)
    expect(item.querySelector('[data-slot="toast-title"]').textContent).toBe('Event created')
    expect(item.querySelector('[data-slot="toast-description"]').textContent).toBe('Sunday at 9:00')
    expect(item.getAttribute('aria-labelledby')).toBe(item.querySelector('[data-slot="toast-title"]').id)
    expect(item.querySelector('[data-toast-close]').getAttribute('aria-label')).toBe('Close toast')
    expect(item.querySelector('[data-slot="toast-icon"]')).toBeNull()
})

it('adds an icon for each type; errors are alerts', () => {
    viewport.dataset.limit = '10'

    for (const type of ['success', 'info', 'warning', 'error', 'loading']) toast({ message: type, type, duration: 0 })

    expect(viewport.querySelectorAll('[data-slot="toast-icon"] svg')).toHaveLength(5)
    expect(viewport.querySelector('[data-type="error"]').getAttribute('role')).toBe('alertdialog')
    expect(viewport.querySelector('[data-type="loading"] svg[data-spin]')).not.toBeNull()
})

it('treats text as text', () => {
    toast({ message: '<img src=x>' })

    expect(viewport.querySelector('img')).toBeNull()
    expect(newest().querySelector('[data-slot="toast-title"]').textContent).toBe('<img src=x>')
})

it('stacks the newest in front and keeps three', () => {
    for (const message of ['a', 'b', 'c', 'd']) toast({ message })

    expect(live().map(item => item.textContent)).toEqual(['b', 'c', 'd'])
    expect(live().map(item => item.style.getPropertyValue('--toast-index'))).toEqual(['2', '1', '0'])
    expect(live().map(item => item.firstElementChild.hasAttribute('data-behind'))).toEqual([true, true, false])
    expect(viewport.querySelector('[data-limited]').textContent).toBe('a')
})

it('spreads the stack out and pauses while hovered', () => {
    toast({ message: 'Saved', duration: 1000 })
    vi.advanceTimersByTime(600)

    newest().dispatchEvent(new MouseEvent('pointerover', { bubbles: true }))
    expect(newest().hasAttribute('data-expanded')).toBe(true)

    vi.advanceTimersByTime(5000)
    expect(live()).toHaveLength(1)

    newest().dispatchEvent(new MouseEvent('pointerout', { bubbles: true, relatedTarget: document.body }))
    vi.advanceTimersByTime(399)
    expect(live()).toHaveLength(1)

    vi.advanceTimersByTime(1)
    expect(live()).toHaveLength(0)
})

it('closes after the timeout and hides the empty viewport', () => {
    toast({ message: 'Saved' })

    vi.advanceTimersByTime(4999)
    expect(live()).toHaveLength(1)

    vi.advanceTimersByTime(1)
    expect(live()).toHaveLength(0)

    vi.advanceTimersByTime(500)
    expect(viewport.children).toHaveLength(0)
    expect(viewport.matches(':popover-open')).toBe(false)
})

it('keeps loading toasts until they are replaced by id', () => {
    toast({ id: 'save', message: 'Saving…', type: 'loading' })
    vi.advanceTimersByTime(60000)

    const item = newest()

    toast({ id: 'save', message: 'Saved', type: 'success' })

    expect(live()).toEqual([item])
    expect(item.dataset.type).toBe('success')
    expect(item.querySelector('[data-slot="toast-title"]').textContent).toBe('Saved')

    vi.advanceTimersByTime(5000)
    expect(live()).toHaveLength(0)
})

it('closes from its button and runs its action on the Livewire component', () => {
    const call = vi.fn()
    window.Livewire = { find: id => (id === 'c1' ? { call } : null) }

    toast({ message: 'Deleted', action: { label: 'Undo', component: 'c1', method: 'restore', params: [7] } })

    const action = newest().querySelector('[data-toast-action]')

    expect(action.textContent).toBe('Undo')
    expect(action.dataset.variant).toBe('outline')
    action.click()

    expect(call).toHaveBeenCalledWith('restore', 7)
    expect(live()).toHaveLength(0)

    toast({ message: 'Saved' })
    newest().querySelector('[data-toast-close]').click()
    expect(live()).toHaveLength(0)

    delete window.Livewire
})

it('closes when swiped down or right past the threshold', () => {
    toast({ message: 'Saved' })

    const item = newest()
    const swipe = (dx, dy) => {
        item.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, button: 0, clientX: 0, clientY: 0 }))
        window.dispatchEvent(new MouseEvent('pointermove', { clientX: dx, clientY: dy }))
        window.dispatchEvent(new MouseEvent('pointerup', {}))
    }

    swipe(0, 20)
    expect(live()).toHaveLength(1)
    expect(item.style.getPropertyValue('--toast-swipe-movement-y')).toBe('0px')

    swipe(60, 0)
    expect(live()).toHaveLength(0)
    expect(item.dataset.swipeDirection).toBe('right')
})

it('does nothing without a viewport or any text', () => {
    toast({})
    expect(live()).toHaveLength(0)

    document.body.innerHTML = ''
    expect(() => toast({ message: 'x' })).not.toThrow()
})
