import { control } from '../behaviors/control.js'

export function slider() {
    let min, max, step, orientation, inverted, range
    const thumbAttributes = new WeakMap()
    let active = null,
        dragSource
    const snap = (number) => {
        range.value = number
        return range.valueAsNumber
    }
    return {
        positions: [],
        ...control(),
        init() {
            // Use the browser's range sanitization, including decimal steps and uneven endpoints.
            range = this.$root.querySelector('input[type=range]').cloneNode(false)
            this.initControl()
            this.refresh()
        },
        refresh() {
            ;({ min, max, step, orientation, inverted } = this.config)
            min = Number(min)
            max = Number(max)
            step = Number(step)
            Object.assign(range, { min, max, step })
            const values = Array.isArray(this.value) ? this.value : [this.value ?? min]
            this.positions = values.map(snap).sort((a, b) => a - b)
            if (
                this.config.mode !== 'controlled' &&
                values.some((n, i) => Number(n) !== this.positions[i])
            ) {
                this.value = Array.isArray(this.value) ? this.positions : this.positions[0]
                this.signal('input')
            }
        },
        syncThumb(input, index) {
            // Internal handles live for the mount, while their native attributes follow Blade.
            const { thumb, id, label } = this.config
            const attributes = {
                ...thumb,
                id: id && index ? id + '-' + index : id,
                'aria-label': thumb['aria-label'] ?? label.replace('{n}', index + 1),
            }
            for (const name of thumbAttributes.get(input) ?? [])
                if (!(name in attributes)) input.removeAttribute(name)
            for (const [name, value] of Object.entries(attributes)) {
                if (value == null || value === false) input.removeAttribute(name)
                else input.setAttribute(name, value === true ? name : value)
            }
            thumbAttributes.set(input, Object.keys(attributes))
        },
        offset(index, center = false) {
            const fraction = (this.positions[index] - min) / (max - min)
            return `calc(${fraction * 100}% + ${(center ? 0.5 : 0) - fraction} * var(--slider-thumb-size))`
        },
        change(index, number) {
            if (this.config.disabled || this.config.readonly) return
            const next = snap(number)
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
            const thumb = this.$root.querySelector('[data-slider-index]').getBoundingClientRect()
            const fraction =
                orientation === 'vertical'
                    ? 1 -
                      (event.clientY - box.top - thumb.height / 2) /
                          Math.max(1, box.height - thumb.height)
                    : (event.clientX - box.left - thumb.width / 2) /
                      Math.max(1, box.width - thumb.width)
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
