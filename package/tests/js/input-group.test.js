import { beforeAll, beforeEach, expect, it } from 'vitest'
import { initInputGroups } from '../../resources/js/input-group.js'

beforeAll(() => initInputGroups())

beforeEach(() => {
    document.body.innerHTML = `
        <div data-slot="input-group">
            <input data-slot="input-group-control">
            <div data-slot="input-group-addon"><span>https://</span><button type="button">Clear</button></div>
        </div>
    `
})

const input = () => document.querySelector('input')

it('focuses the input when an addon is clicked', () => {
    document.querySelector('span').click()

    expect(document.activeElement).toBe(input())
})

it('leaves focus alone when a button in the addon is clicked', () => {
    const button = document.querySelector('button')
    button.focus()
    button.click()

    expect(document.activeElement).toBe(button)
})
