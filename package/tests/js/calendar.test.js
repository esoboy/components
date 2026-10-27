import { afterEach, beforeAll, describe, expect, it } from 'vitest'
import { defineCalendar, fromKey, toKey, weekStart } from '../../resources/js/calendar.js'
import { defineDatePicker } from '../../resources/js/date-picker.js'

beforeAll(() => {
    defineCalendar()
    defineDatePicker()
})

afterEach(() => {
    document.body.innerHTML = ''
})

function mount(attributes = '') {
    document.body.innerHTML = `<ui-calendar data-locale="en-US" ${attributes.includes('data-week-starts-on') ? '' : 'data-week-starts-on="0"'} ${attributes}></ui-calendar>`

    return document.querySelector('ui-calendar')
}

const day = (calendar, key) => calendar.querySelector(`button[data-day="${key}"]`)
const announced = calendar => {
    const seen = []
    calendar.addEventListener('input', event => event.target === calendar && seen.push(JSON.stringify(calendar.value)))
    return seen
}

describe('dates', () => {
    it('round-trips Y-m-d keys in local time', () => {
        expect(toKey(fromKey('2026-02-28'))).toBe('2026-02-28')
        expect(toKey(new Date(2026, 0, 5))).toBe('2026-01-05')
    })

    it('reads the first day of the week from the locale', () => {
        expect(weekStart('en-US')).toBe(0)
        expect(weekStart('zh-CN')).toBe(1)
    })
})

describe('rendering', () => {
    it('shows the selected month with outside days', () => {
        const calendar = mount('data-value=\'"2026-10-26"\'')

        expect(calendar.month).toBe('2026-10')
        expect(calendar.querySelector('[data-slot="calendar-caption-label"]').textContent).toBe('October 2026')
        expect([...calendar.querySelectorAll('[data-slot="calendar-weekday"]')].map(cell => cell.textContent)).toEqual(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'])
        expect(calendar.querySelectorAll('[data-slot="calendar-week"]')).toHaveLength(5)
        expect(day(calendar, '2026-09-27').dataset.outside).toBe('true')
        expect(day(calendar, '2026-10-26').dataset.selectedSingle).toBe('true')
    })

    it('hides outside days when asked', () => {
        const calendar = mount('data-default-month="2026-10" data-show-outside-days="false"')

        expect(day(calendar, '2026-09-27')).toBeNull()
        expect(calendar.querySelectorAll('td[data-hidden]').length).toBeGreaterThan(0)
    })

    it('starts weeks on Monday when told to', () => {
        const calendar = mount('data-default-month="2026-10" data-week-starts-on="1"')

        expect(calendar.querySelector('[data-slot="calendar-weekday"]').textContent).toBe('Mo')
        expect(calendar.querySelector('button[data-day]').dataset.day).toBe('2026-09-28')
    })

    it('renders month and year dropdowns', () => {
        const calendar = mount('data-default-month="2026-10" data-caption-layout="dropdown" data-label-month="Month" data-label-year="Year"')

        expect(calendar.querySelector('select[data-calendar-month]').value).toBe('9')
        expect(calendar.querySelector('select[data-calendar-year]').value).toBe('2026')
        expect(calendar.querySelector('select[data-calendar-year]').getAttribute('aria-label')).toBe('Year')
    })

    it('renders several months side by side', () => {
        const calendar = mount('data-default-month="2026-12" data-number-of-months="2"')

        expect([...calendar.querySelectorAll('[data-slot="calendar-caption-label"]')].map(label => label.textContent)).toEqual(['December 2026', 'January 2027'])
    })
})

describe('selecting', () => {
    it('selects one day', () => {
        const calendar = mount('data-default-month="2026-10"')
        const seen = announced(calendar)

        day(calendar, '2026-10-15').click()

        expect(calendar.value).toBe('2026-10-15')
        expect(seen).toEqual(['"2026-10-15"'])
    })

    it('toggles days in multiple mode', () => {
        const calendar = mount('data-mode="multiple" data-default-month="2026-10"')

        day(calendar, '2026-10-15').click()
        day(calendar, '2026-10-03').click()
        day(calendar, '2026-10-15').click()

        expect(calendar.value).toEqual(['2026-10-03'])
    })

    it('builds a range, swapping when the second day comes first', () => {
        const calendar = mount('data-mode="range" data-default-month="2026-10"')

        day(calendar, '2026-10-15').click()
        expect(calendar.value).toEqual({ from: '2026-10-15', to: null })

        day(calendar, '2026-10-10').click()
        expect(calendar.value).toEqual({ from: '2026-10-10', to: '2026-10-15' })

        expect(day(calendar, '2026-10-10').dataset.rangeStart).toBe('true')
        expect(day(calendar, '2026-10-12').dataset.rangeMiddle).toBe('true')
        expect(day(calendar, '2026-10-15').dataset.rangeEnd).toBe('true')

        day(calendar, '2026-10-20').click()
        expect(calendar.value).toEqual({ from: '2026-10-20', to: null })
    })

    it('refuses days outside min and max or disabled', () => {
        const calendar = mount('data-default-month="2026-10" data-min="2026-10-05" data-max="2026-10-25" data-disabled-dates=\'["2026-10-10"]\'')

        for (const key of ['2026-10-04', '2026-10-26', '2026-10-10']) {
            expect(day(calendar, key).getAttribute('aria-disabled')).toBe('true')
            day(calendar, key).click()
        }

        expect(calendar.value).toBeNull()
        expect(calendar.querySelector('[data-calendar-previous]').getAttribute('aria-disabled')).toBe('true')
    })

    it('jumps to a value set by Livewire', () => {
        const calendar = mount('data-default-month="2026-10"')

        calendar.value = '2027-02-14'

        expect(calendar.month).toBe('2027-02')
        expect(day(calendar, '2027-02-14').dataset.selectedSingle).toBe('true')
    })
})

describe('navigation', () => {
    it('moves between months with the buttons and dropdowns', () => {
        const calendar = mount('data-default-month="2026-10" data-caption-layout="dropdown"')

        calendar.querySelector('[data-calendar-next]').click()
        expect(calendar.month).toBe('2026-11')

        const year = calendar.querySelector('select[data-calendar-year]')
        year.value = '2030'
        year.dispatchEvent(new Event('change', { bubbles: true }))
        expect(calendar.month).toBe('2030-11')
    })

    it('moves focus with the keyboard, across months', () => {
        const calendar = mount('data-value=\'"2026-10-31"\'')
        const press = name => document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key: name, bubbles: true, cancelable: true }))

        day(calendar, '2026-10-31').focus()
        press('ArrowRight')

        expect(calendar.month).toBe('2026-11')
        expect(document.activeElement.dataset.day).toBe('2026-11-01')

        press('ArrowUp')
        expect(document.activeElement.dataset.day).toBe('2026-10-25')
    })
})

describe('date picker', () => {
    function picker(mode = 'single', value = null) {
        document.body.innerHTML = `
            <ui-date-picker data-placeholder="Pick a date" data-locale="en-US">
                <button data-date-picker-trigger><span data-slot="date-picker-value">Pick a date</span></button>
                <ui-calendar data-mode="${mode}" data-locale="en-US" data-week-starts-on="0" data-default-month="2026-10" ${value ? `data-value='${JSON.stringify(value)}'` : ''}></ui-calendar>
            </ui-date-picker>`

        return document.querySelector('ui-date-picker')
    }

    const text = element => element.querySelector('[data-slot="date-picker-value"]').textContent

    it('shows the placeholder when empty', async () => {
        const element = picker()

        await Promise.resolve()

        expect(text(element)).toBe('Pick a date')
        expect(element.trigger.dataset.empty).toBe('true')
    })

    it('formats a chosen day and announces it from the picker', () => {
        const element = picker()
        const seen = []

        element.addEventListener('input', event => event.target === element && seen.push(element.value))
        day(element.calendar, '2026-10-26').click()

        expect(text(element)).toBe('October 26, 2026')
        expect(element.trigger.dataset.empty).toBe('false')
        expect(seen).toEqual(['2026-10-26'])
    })

    it('formats a range once the calendar has rendered', async () => {
        const element = picker('range', { from: '2026-10-20', to: '2026-11-03' })

        await Promise.resolve()

        expect(text(element)).toBe('Oct 20, 2026 - Nov 03, 2026')
    })

    it('applies a preset, showing its month', () => {
        const element = picker('range')
        const seen = []

        element.querySelector('ui-calendar').insertAdjacentHTML('afterend', `<button data-date-picker-preset='{"from":"2026-12-01","to":"2026-12-07"}'></button>`)
        element.addEventListener('change', event => event.target === element && seen.push(element.value))
        element.querySelector('[data-date-picker-preset]').click()

        expect(seen).toEqual([{ from: '2026-12-01', to: '2026-12-07' }])
        expect(element.calendar.month).toBe('2026-12')
        expect(text(element)).toBe('Dec 01, 2026 - Dec 07, 2026')
    })

    it('proxies values set by Livewire to the calendar', () => {
        const element = picker()

        element.value = '2026-01-05'

        expect(element.calendar.value).toBe('2026-01-05')
        expect(text(element)).toBe('January 5, 2026')
    })
})
