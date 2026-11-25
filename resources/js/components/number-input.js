export function number() {
    return {
        increase: true,
        decrease: true,
        init() {
            const initial = this.$refs.number.defaultValue
            this.onReset = (event) => {
                if (event.target !== this.$refs.number.form) return
                queueMicrotask(() => {
                    if (event.defaultPrevented || !this.$root.isConnected) return
                    const input = this.$refs.number
                    if (this.$root.dataset.mode !== 'controlled') input.value = initial
                    this.notify()
                })
            }
            this.$root.getRootNode().addEventListener('reset', this.onReset, true)
        },
        measure() {
            const input = this.$refs.number
            if (!input) return
            const probe = input.cloneNode()
            probe.value = input.value
            probe.stepDown()
            this.decrease = !input.matches(':disabled, [readonly]') && probe.value !== input.value
            probe.value = input.value
            probe.stepUp()
            this.increase = !input.matches(':disabled, [readonly]') && probe.value !== input.value
        },
        typed(event) {
            const input = event.target
            if (input !== this.$refs.number) return
            if (
                input.validity.badInput ||
                input.validity.rangeUnderflow ||
                input.validity.rangeOverflow
            ) {
                event.stopPropagation()
                return
            }
            this.$dispatch('value-changed', {
                value: input.value === '' ? null : input.valueAsNumber,
            })
            if (this.$root.dataset.mode === 'controlled') input.value = input.defaultValue
            this.measure()
        },
        notify() {
            const input = this.$refs.number
            input.dispatchEvent(new Event('input', { bubbles: true }))
            input.dispatchEvent(new Event('change', { bubbles: true }))
        },
        step(direction) {
            const input = this.$refs.number
            if (input.matches(':disabled, [readonly]')) return
            if (direction > 0) input.stepUp()
            else input.stepDown()
            this.notify()
        },
        commit() {
            const input = this.$refs.number
            if (input.validity.rangeUnderflow || input.validity.rangeOverflow) {
                input.stepUp(input.validity.rangeUnderflow ? 1 : -1)
                input.dispatchEvent(new Event('input', { bubbles: true }))
            }
            this.measure()
        },
        destroy() {
            this.$root.getRootNode().removeEventListener('reset', this.onReset, true)
        },
    }
}
