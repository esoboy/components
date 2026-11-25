import { clamp } from '../behaviors/clamp.js'

function currentPercent(handle) {
    const first = Number(getComputedStyle(handle.previousElementSibling).flexGrow)
    const second = Number(getComputedStyle(handle.nextElementSibling).flexGrow)
    return (first / (first + second)) * 100
}

export function resizable(direction = 'horizontal') {
    const horizontal = direction === 'horizontal'
    const coordinate = horizontal ? 'clientX' : 'clientY'
    const dimension = horizontal ? 'width' : 'height'
    let drag
    return {
        direction,
        syncHandle(handle) {
            if (handle?.matches('.ui-resizable-handle'))
                handle.ariaValueNow = String(Math.round(currentPercent(handle)))
        },
        start(event) {
            const handle = event.currentTarget
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            if (!first || !second) return
            const size = first.getBoundingClientRect()[dimension]
            drag = {
                pointer: event.pointerId,
                size,
                total: size + second.getBoundingClientRect()[dimension],
                start: event[coordinate],
            }
            handle.setPointerCapture(event.pointerId)
        },
        move(event) {
            const handle = event.currentTarget
            if (drag?.pointer === event.pointerId && handle.hasPointerCapture(event.pointerId))
                this.resize(
                    handle,
                    ((drag.size + event[coordinate] - drag.start) / drag.total) * 100,
                )
        },
        resize(handle, percent) {
            percent = clamp(percent, 10, 90)
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            if (!first || !second) return
            const total =
                Number(getComputedStyle(first).flexGrow) + Number(getComputedStyle(second).flexGrow)
            first.style.setProperty('--panel-size', (total * percent) / 100)
            second.style.setProperty('--panel-size', (total * (100 - percent)) / 100)
            handle.ariaValueNow = String(Math.round(percent))
            this.syncHandle(first.previousElementSibling)
            this.syncHandle(second.nextElementSibling)
        },
        nudge(event, axis, step) {
            if (direction !== axis) return
            event.preventDefault()
            this.resize(event.currentTarget, currentPercent(event.currentTarget) + step)
        },
    }
}
