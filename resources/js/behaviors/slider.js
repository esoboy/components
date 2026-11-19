import { control } from './control.js'

export function slider() {
    let min, max, step, orientation, inverted
    let active = null,
        dragSource
    const clamp = (number) =>
        Math.max(
            min,
            Math.min(max, Number((min + Math.round((number - min) / step) * step).toFixed(12))),
        )
    return {
        positions: [],
        ...control(),
        init() {
            this.initControl()
            this.refresh()
        },
        refresh() {
            ;({ min, max, step, orientation, inverted } = this.config)
            min = Number(min)
            max = Number(max)
            step = Number(step)
            this.positions = Array.isArray(this.value)
                ? [...this.value].map(Number).sort((a, b) => a - b)
                : [Number(this.value ?? min)]
        },
        percent(index) {
            return ((this.positions[index] - min) / (max - min)) * 100
        },
        rangeStyle() {
            const first = this.positions.length > 1 ? this.percent(0) : 0,
                last = this.percent(this.positions.length - 1)
            const start =
                    this.positions.length > 1 ? `calc(${first}% + ${6 - first * 0.12}px)` : '0%',
                length =
                    this.positions.length > 1
                        ? `calc(${last - first}% - ${(last - first) * 0.12}px)`
                        : `calc(${last}% + ${6 - last * 0.12}px)`
            return orientation === 'vertical'
                ? `${inverted ? 'top' : 'bottom'}:${start};height:${length}`
                : `${inverted ? 'right' : 'left'}:${start};width:${length}`
        },
        thumbStyle(index) {
            return `${orientation === 'vertical' ? (inverted ? 'top' : 'bottom') : inverted ? 'right' : 'left'}:calc(${this.percent(index)}% - ${this.percent(index) * 0.12}px)`
        },
        change(index, number) {
            if (this.config.disabled || this.config.readonly) return
            const next = clamp(Number(number))
            this.positions[index] = Math.max(
                this.positions[index - 1] ?? min,
                Math.min(this.positions[index + 1] ?? max, next),
            )
            const value =
                this.positions.length === 1 && !Array.isArray(this.value)
                    ? this.positions[0]
                    : [...this.positions]
            if (this.config.mode === 'controlled' && active !== null) return
            this.write(value)
        },
        coordinate(event) {
            const box = this.$root.getBoundingClientRect()
            const fraction =
                orientation === 'vertical'
                    ? 1 - (event.clientY - box.top - 6) / Math.max(1, box.height - 12)
                    : (event.clientX - box.left - 6) / Math.max(1, box.width - 12)
            return min + (inverted ? 1 - fraction : fraction) * (max - min)
        },
        start(event) {
            if (event.button !== 0 || this.config.disabled || this.config.readonly) return
            event.preventDefault()
            const value = this.coordinate(event)
            active = event.target.dataset.sliderIndex
                ? Number(event.target.dataset.sliderIndex)
                : this.positions.reduce(
                      (nearest, number, index) =>
                          Math.abs(number - value) < Math.abs(this.positions[nearest] - value)
                              ? index
                              : nearest,
                      0,
                  )
            this.$root.querySelectorAll('[data-slider-index]')[active]?.focus()
            this.$root.setPointerCapture(event.pointerId)
            dragSource = event.pointerId
            this.change(active, value)
        },
        move(event) {
            if (active !== null && dragSource === event.pointerId)
                this.change(active, this.coordinate(event))
        },
        finish(event) {
            if (dragSource === event.pointerId) {
                const index = active
                active = null
                dragSource = null
                this.change(index, this.positions[index])
                this.signal('change')
                if (this.$root.hasPointerCapture(event.pointerId))
                    this.$root.releasePointerCapture(event.pointerId)
            }
        },
        key(event, index) {
            const delta = {
                ArrowUp: step,
                ArrowRight: step,
                ArrowDown: -step,
                ArrowLeft: -step,
                PageUp: step * 10,
                PageDown: -step * 10,
            }
            if (!(event.key in delta) && !['Home', 'End'].includes(event.key)) return
            event.preventDefault()
            this.change(
                index,
                event.key === 'Home'
                    ? min
                    : event.key === 'End'
                      ? max
                      : this.positions[index] + delta[event.key] * (inverted ? -1 : 1),
            )
            this.signal('change')
        },
    }
}
