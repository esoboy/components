import { control } from './control.js'

export function number() {
    return {
        ...control(),
        init() {
            this.initControl()
            this.refresh()
        },
        refresh() {
            const value = String(this.value ?? '')
            if (this.$refs.number.value !== value) this.$refs.number.value = value
        },
        clamp(value) {
            return Math.max(
                this.config.min ?? -Infinity,
                Math.min(this.config.max ?? Infinity, value),
            )
        },
        input({ target }) {
            const { badInput, rangeUnderflow, rangeOverflow } = target.validity
            if (badInput || rangeUnderflow || rangeOverflow) return
            this.write(target.value === '' ? null : target.valueAsNumber)
        },
        commit() {
            const input = this.$refs.number
            if (input.validity.badInput) input.value = this.value ?? ''
            else if (input.validity.rangeUnderflow || input.validity.rangeOverflow)
                this.write(this.clamp(input.valueAsNumber))
        },
        adjust(direction) {
            this.write(
                this.clamp(
                    Number(
                        (
                            Number(this.value ?? this.config.min ?? 0) +
                            direction * Number(this.config.step)
                        ).toFixed(12),
                    ),
                ),
                true,
            )
        },
    }
}
