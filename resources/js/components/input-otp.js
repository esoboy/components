import { control } from '../behaviors/control.js'
import { clamp } from '../behaviors/clamp.js'

export function otp() {
    return {
        ...control(),
        get code() {
            return this.value ?? ''
        },
        get slots() {
            return this.$root.querySelectorAll('[data-otp-index]')
        },
        init() {
            this.initControl()
        },
        refresh() {
            this.slots.forEach((input, index) => {
                input.value = this.code[index] ?? ''
            })
        },
        focus(index) {
            const slots = this.slots
            slots[clamp(index, 0, slots.length - 1)]?.focus()
        },
        update(value) {
            this.write(value.slice(0, this.config.length), true)
        },
        insert(value, index) {
            if (this.config.disabled || this.config.readonly) return
            const clean = value.replace(
                this.config.pattern === 'numeric' ? /[^0-9]/g : /[^a-z0-9]/gi,
                '',
            )
            if (!clean) return
            const start = Math.min(index, this.code.length)
            this.update(this.code.slice(0, start) + clean + this.code.slice(start + clean.length))
            this.focus(start + clean.length)
        },
        input(event, index) {
            if (event.target.value === '')
                this.update(this.code.slice(0, index) + this.code.slice(index + 1))
            else this.insert(event.target.value, index)
            event.target.value = this.code[index] ?? ''
        },
        key(event, index) {
            const next = {
                ArrowLeft: index - 1,
                ArrowRight: index + 1,
                Home: 0,
                End: this.config.length - 1,
            }[event.key]
            if (next !== undefined) {
                event.preventDefault()
                this.focus(next)
            }
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                event.preventDefault()
                this.update(this.code.slice(0, index - 1) + this.code.slice(index))
                this.focus(index - 1)
            }
        },
    }
}
