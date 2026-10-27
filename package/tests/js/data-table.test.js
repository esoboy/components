import { afterEach, beforeAll, expect, it, vi } from 'vitest'
import { register as registerDataTable } from '../../resources/js/components/data-table.js'

beforeAll(() => registerDataTable())

afterEach(() => {
    document.body.innerHTML = ''
})

function mount() {
    document.body.innerHTML = `
        <eso-data-table>
            <table>
                <thead><tr><th><input type="checkbox" data-data-table-select-all></th></tr></thead>
                <tbody>
                    <tr><td><input type="checkbox" value="1" data-data-table-select-row></td></tr>
                    <tr><td><input type="checkbox" value="2" data-data-table-select-row></td></tr>
                    <tr><td><input type="checkbox" value="3" data-data-table-select-row disabled></td></tr>
                </tbody>
            </table>
        </eso-data-table>`

    return document.querySelector('eso-data-table')
}

const all = () => document.querySelector('[data-data-table-select-all]')
const rows = () => [...document.querySelectorAll('[data-data-table-select-row]')]

it('checks every enabled row from the select-all box, announcing each', () => {
    mount()
    const changed = vi.fn()
    document.addEventListener('change', changed)

    all().click()

    expect(rows().map(box => box.checked)).toEqual([true, true, false])
    expect(changed.mock.calls.filter(([event]) => event.target.matches('[data-data-table-select-row]'))).toHaveLength(2)
    expect([...document.querySelectorAll('tbody tr')].map(row => row.getAttribute('data-state'))).toEqual(['selected', 'selected', null])

    all().click()
    expect(rows().map(box => box.checked)).toEqual([false, false, false])
    document.removeEventListener('change', changed)
})

it('shows mixed while only some rows are checked', () => {
    mount()

    rows()[0].click()

    expect(all().indeterminate).toBe(true)
    expect(all().checked).toBe(false)

    rows()[1].click()

    expect(all().indeterminate).toBe(false)
    expect(all().checked).toBe(true)
})

it('follows rows checked by a morph', async () => {
    mount()

    rows()[0].setAttribute('checked', '')
    rows()[0].checked = true

    await vi.waitFor(() => expect(rows()[0].closest('tr').getAttribute('data-state')).toBe('selected'))
    expect(all().indeterminate).toBe(true)
})
