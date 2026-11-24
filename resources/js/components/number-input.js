import { control } from '../behaviors/control.js'

export function number() {
    return {
        ...control(),
        init() {
            this.initControl()
            this.refresh()
        },
        refresh() {
            const input = this.$refs.number
            const value = String(this.value ?? '')
            // Native editing can spell the same number as "1.", "1.0" or "01".
            if (input.value === value || (value !== '' && input.valueAsNumber === Number(value)))
                return
            input.value = value
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
