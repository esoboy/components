import { beforeAll, beforeEach, expect, it } from 'vitest'
import { register as registerTooltip } from '../../resources/js/components/tooltip.js'

let trigger
let tooltip

beforeAll(() => registerTooltip())

beforeEach(() => {
    document.body.innerHTML = `
        <button data-tooltip="t"><span>Hover</span></button>
        <div id="t" popover="manual" data-slot="tooltip-content">Tip</div>
        <a id="elsewhere">x</a>
    `
    trigger = document.querySelector('[data-tooltip]')
    tooltip = document.getElementById('t')
})

const open = () => tooltip.matches(':popover-open')
const pointer = (type, target, relatedTarget = null) => target.dispatchEvent(new MouseEvent(type, { bubbles: true, relatedTarget }))

it('shows on hover and hides when the pointer leaves', () => {
    pointer('pointerover', trigger)
    expect(open()).toBe(true)

    pointer('pointerout', trigger, document.getElementById('elsewhere'))
    expect(open()).toBe(false)
})

it('stays while the pointer moves inside the trigger', () => {
    pointer('pointerover', trigger)
    pointer('pointerout', trigger, trigger.querySelector('span'))

    expect(open()).toBe(true)
})

it('shows on focus and hides on blur', () => {
    trigger.dispatchEvent(new FocusEvent('focusin', { bubbles: true }))
    expect(open()).toBe(true)

    trigger.dispatchEvent(new FocusEvent('focusout', { bubbles: true, relatedTarget: document.getElementById('elsewhere') }))
    expect(open()).toBe(false)
})

it('hides on Escape', () => {
    pointer('pointerover', trigger)
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))

    expect(open()).toBe(false)
})
