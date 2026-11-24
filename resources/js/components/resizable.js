export function resizable(direction = 'horizontal') {
    return {
        direction,
        drag: null,
        init() {
            this.$nextTick(() =>
                this.$root.querySelectorAll('.ui-resizable-handle').forEach((handle) => {
                    if (handle.closest('.ui-resizable') !== this.$root) return
                    const first = Number(getComputedStyle(handle.previousElementSibling).flexGrow),
                        second = Number(getComputedStyle(handle.nextElementSibling).flexGrow)
                    handle.setAttribute(
                        'aria-valuenow',
                        String(Math.round((first / (first + second)) * 100)),
                    )
                }),
            )
        },
        start(event) {
            const handle = event.currentTarget
            if (!handle.previousElementSibling || !handle.nextElementSibling) return
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            const axis = this.direction === 'horizontal' ? 'width' : 'height'
            this.drag = {
                handle,
                first,
                second,
                total: first.getBoundingClientRect()[axis] + second.getBoundingClientRect()[axis],
                start: this.direction === 'horizontal' ? event.clientX : event.clientY,
                size: first.getBoundingClientRect()[axis],
            }
            handle.setPointerCapture(event.pointerId)
        },
        move(event) {
            if (!this.drag) return
            const position = this.direction === 'horizontal' ? event.clientX : event.clientY
            this.resize(
                this.drag.handle,
                ((this.drag.size + position - this.drag.start) / this.drag.total) * 100,
            )
        },
        resize(handle, percent) {
            percent = Math.max(10, Math.min(90, percent))
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            if (!first || !second) return
            const total =
                Number(getComputedStyle(first).flexGrow) + Number(getComputedStyle(second).flexGrow)
            first.style.setProperty('--panel-size', (total * percent) / 100)
            second.style.setProperty('--panel-size', (total * (100 - percent)) / 100)
            handle.setAttribute('aria-valuenow', String(Math.round(percent)))
        },
        key(event) {
            const keys =
                this.direction === 'horizontal'
                    ? ['ArrowLeft', 'ArrowRight']
                    : ['ArrowUp', 'ArrowDown']
            const value = Number(event.currentTarget.getAttribute('aria-valuenow'))
            if (keys.includes(event.key) || ['Home', 'End'].includes(event.key)) {
                event.preventDefault()
                this.resize(
                    event.currentTarget,
                    event.key === 'Home'
                        ? 10
                        : event.key === 'End'
                          ? 90
                          : value + (event.key === keys[0] ? -5 : 5),
                )
            }
        },
        finish() {
            this.drag = null
        },
        destroy() {
            this.finish()
        },
    }
}
