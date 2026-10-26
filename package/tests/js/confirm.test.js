import { beforeAll, beforeEach, expect, it, vi } from 'vitest'
import { initConfirm } from '../../resources/js/confirm.js'

let directiveInit

beforeAll(() => {
    window.Livewire = { hook: (name, callback) => name === 'directive.init' && (directiveInit = callback) }
    initConfirm()
    document.dispatchEvent(new Event('livewire:init'))
})

beforeEach(() => {
    document.body.innerHTML = `
        <dialog id="ui-confirm">
            <h2 data-slot="alert-dialog-title">Are you sure?</h2>
            <button data-cancel>Cancel</button>
            <button value="confirm">Continue</button>
        </dialog>
        <button id="delete"></button>
    `
})

// What Livewire does for wire:confirm="...": its own directive first, then ours.
function bind(expression, modifiers = []) {
    const el = document.getElementById('delete')
    el.__livewire_confirm = vi.fn()
    directiveInit({ el, directive: { value: 'confirm', expression, modifiers } })
    return el
}

const dialog = () => document.getElementById('ui-confirm')
const title = () => dialog().querySelector('[data-slot="alert-dialog-title"]').textContent

it('asks with the alert dialog and runs the action on confirm', async () => {
    const action = vi.fn()
    const instead = vi.fn()

    bind('Delete this post?').__livewire_confirm(action, instead)

    expect(dialog().open).toBe(true)
    expect(title()).toBe('Delete this post?')

    dialog().close('confirm')
    await Promise.resolve()

    expect(action).toHaveBeenCalled()
    expect(instead).not.toHaveBeenCalled()
})

it('treats cancel and Escape as declining', async () => {
    const action = vi.fn()
    const instead = vi.fn()

    bind('Delete?').__livewire_confirm(action, instead)
    dialog().close()
    await Promise.resolve()

    expect(action).not.toHaveBeenCalled()
    expect(instead).toHaveBeenCalled()
})

it('falls back to the translated title without a message', () => {
    bind('Delete?').__livewire_confirm(vi.fn(), vi.fn())
    dialog().close()

    bind('').__livewire_confirm(vi.fn(), vi.fn())

    expect(title()).toBe('Are you sure?')
})

it('leaves wire:confirm.prompt to Livewire', () => {
    const el = document.getElementById('delete')
    const livewire = (el.__livewire_confirm = vi.fn())

    directiveInit({ el, directive: { value: 'confirm', expression: 'Type DELETE|DELETE', modifiers: ['prompt'] } })

    expect(el.__livewire_confirm).toBe(livewire)
})

it('falls back to Livewire\'s confirm when the layout has no dialog', () => {
    const el = document.getElementById('delete')
    const livewire = (el.__livewire_confirm = vi.fn())

    directiveInit({ el, directive: { value: 'confirm', expression: 'Delete?', modifiers: [] } })
    dialog().remove()
    el.__livewire_confirm(vi.fn(), vi.fn())

    expect(livewire).toHaveBeenCalled()
})
