import { afterEach, beforeAll, beforeEach, expect, it, vi } from 'vitest'
import { initToasts } from '../../resources/js/toast.js'

let toaster

beforeAll(() => initToasts())

beforeEach(() => {
    vi.useFakeTimers()
    document.body.innerHTML = '<ol data-slot="toaster" popover="manual"></ol>'
    toaster = document.querySelector('[data-slot="toaster"]')
})

afterEach(() => {
    vi.runAllTimers()
    vi.useRealTimers()
})

const toast = detail => window.dispatchEvent(new CustomEvent('ui-toast', { detail }))
const live = () => [...toaster.querySelectorAll('[data-slot="toast"]:not([data-removed])')]

it('shows a message with its description', () => {
    toast({ message: 'Event created', description: 'Sunday at 9:00' })

    const [item] = live()

    expect(toaster.matches(':popover-open')).toBe(true)
    expect(item.dataset.type).toBe('default')
    expect(item.querySelector('[data-slot="toast-title"]').textContent).toBe('Event created')
    expect(item.querySelector('[data-slot="toast-description"]').textContent).toBe('Sunday at 9:00')
    expect(item.querySelector('[data-slot="toast-icon"]')).toBeNull()
})

it('adds an icon for each type', () => {
    for (const type of ['success', 'info', 'warning', 'error']) toast({ message: type, type })

    expect(toaster.querySelectorAll('[data-slot="toast-icon"] svg')).toHaveLength(4)
})

it('treats text as text', () => {
    toast({ message: '<img src=x>' })

    expect(toaster.querySelector('img')).toBeNull()
    expect(live()[0].textContent).toBe('<img src=x>')
})

it('keeps three toasts on screen', () => {
    for (const message of ['a', 'b', 'c', 'd']) toast({ message })

    expect(live().map(item => item.textContent)).toEqual(['b', 'c', 'd'])
})

it('dismisses after the duration and hides the empty toaster', () => {
    toast({ message: 'Saved', duration: 1000 })

    vi.advanceTimersByTime(999)
    expect(live()).toHaveLength(1)

    vi.advanceTimersByTime(1)
    expect(live()).toHaveLength(0)

    vi.advanceTimersByTime(200)
    expect(toaster.children).toHaveLength(0)
    expect(toaster.matches(':popover-open')).toBe(false)
})

it('pauses while hovered', () => {
    toast({ message: 'Saved', duration: 1000 })
    vi.advanceTimersByTime(600)

    live()[0].dispatchEvent(new MouseEvent('pointerover', { bubbles: true }))
    vi.advanceTimersByTime(5000)
    expect(live()).toHaveLength(1)

    live()[0].dispatchEvent(new MouseEvent('pointerout', { bubbles: true, relatedTarget: document.body }))
    vi.advanceTimersByTime(399)
    expect(live()).toHaveLength(1)

    vi.advanceTimersByTime(1)
    expect(live()).toHaveLength(0)
})

it('does nothing without a toaster or a message', () => {
    toast({})
    expect(live()).toHaveLength(0)

    document.body.innerHTML = ''
    expect(() => toast({ message: 'x' })).not.toThrow()
})
