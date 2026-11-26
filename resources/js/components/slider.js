export function slider() {
    let min, max, step, orientation, inverted, initial, stopReset
    let active = null,
        dragSource
    return {
        positions: [],
        get config() {
            return JSON.parse(this.$root.dataset.uiControl)
        },
        get thumbs() {
            return [...this.$root.querySelectorAll('[data-slider-index]')]
        },
        init() {
            initial = this.config.value
            const reset = (event) => {
                if (event.target !== this.thumbs[0]?.form) return
                queueMicrotask(() => {
                    if (event.defaultPrevented || !this.$root.isConnected) return
                    const field = this.$root.querySelector('[data-slider-model]')
                    if (field) {
                        field.edit(initial)
                        field.dispatchEvent(new Event('blur'))
                    }
                    this.refresh()
                    this.$dispatch('value-changed', { value: initial })
                })
            }
            const document = this.$root.getRootNode()
            document.addEventListener('reset', reset, true)
            stopReset = () => document.removeEventListener('reset', reset, true)
            this.refresh()
        },
        destroy() {
            stopReset()
        },
        refresh(config = this.config) {
            ;({ min, max, step, orientation, inverted } = config)
            min = Number(min)
            max = Number(max)
            step = Number(step)
            const thumbs = this.thumbs
            const current = config.model ? this.$wire.$get(config.model) : config.value
            const values = Array.isArray(current)
                ? [...current].sort((a, b) => a - b)
                : [current ?? min]
            this.positions = thumbs.map((thumb, i) => {
                if (thumb.dataset.fieldName) {
                    if (current == null) thumb.removeAttribute('name')
                    else thumb.name = thumb.dataset.fieldName
                }
                if (config.mode === 'controlled' || (config.model && active === null))
                    thumb.value = values[i]
                return thumb.valueAsNumber
            })
        },
        signal(thumb, committed = false) {
            thumb.dispatchEvent(new Event('input', { bubbles: true }))
            if (committed) thumb.dispatchEvent(new Event('change', { bubbles: true }))
        },
        readThumb(index) {
            const thumbs = this.thumbs
            const thumb = thumbs[index]
            const config = this.config
            const number = Math.max(
                thumbs[index - 1]?.valueAsNumber ?? min,
                Math.min(thumbs[index + 1]?.valueAsNumber ?? max, thumb.valueAsNumber),
            )
            thumb.value = number
            const values = thumbs.map((input) => input.valueAsNumber)
            const field = this.$root.querySelector('[data-slider-model]')
            if (field) field.edit(Array.isArray(config.value) ? values : number, false)
            if (thumb.dataset.fieldName && config.mode !== 'controlled')
                thumb.name = thumb.dataset.fieldName
            this.$dispatch('value-changed', {
                value: Array.isArray(config.value) ? values : number,
            })
            if (config.mode === 'controlled' && active === null)
                thumb.value = [config.value].flat().sort((a, b) => a - b)[index]
            this.positions = thumbs.map((input) => input.valueAsNumber)
        },
        commitThumb(type) {
            this.$root
                .querySelector('[data-slider-model]')
                ?.dispatchEvent(
                    type === 'keydown'
                        ? new KeyboardEvent(type, { key: 'Enter', bubbles: true })
                        : new Event(type, { bubbles: true }),
                )
        },
        offset(index, center = false) {
            const fraction = (this.positions[index] - min) / (max - min)
            return `calc(${fraction * 100}% + ${(center ? 0.5 : 0) - fraction} * var(--slider-thumb-size))`
        },
        change(index, number) {
            if (
                this.config.disabled ||
                this.config.readonly ||
                this.$root.querySelector('input[type=range]')?.matches(':disabled')
            )
                return
            const thumb = this.thumbs[index]
            thumb.value = number
            if (this.config.mode === 'controlled' && active !== null) {
                this.positions[index] = thumb.valueAsNumber
                return
            }
            this.signal(thumb)
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
            if (
                event.button !== 0 ||
                this.config.disabled ||
                this.config.readonly ||
                this.$root.querySelector('input[type=range]')?.matches(':disabled')
            )
                return
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
                this.thumbs[index].dispatchEvent(new Event('change', { bubbles: true }))
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
            this.thumbs[index].dispatchEvent(new Event('change', { bubbles: true }))
        },
    }
}
