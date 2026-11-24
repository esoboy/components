import { control } from '../behaviors/control.js'

export function otp() {
    return {
        ...control(),
        get code() {
            return this.value ?? ''
        },
        init() {
            this.initControl()
        },
        refresh() {
            this.$root.querySelectorAll('[data-otp-index]').forEach((input) => {
                input.value = this.code[Number(input.dataset.otpIndex)] ?? ''
            })
        },
        focus(index) {
            this.$root
                .querySelector(
                    `[data-otp-index="${Math.max(0, Math.min(this.config.length - 1, index))}"]`,
                )
                ?.focus()
        },
        update(value) {
            if (this.config.disabled || this.config.readonly) return
            this.write(value.slice(0, this.config.length), true)
        },
        insert(value, index) {
            if (this.config.disabled || this.config.readonly) return
            const clean = value.replace(
                this.config.pattern === 'numeric' ? /[^0-9]/g : /[^a-z0-9]/gi,
                '',
            )
            if (!clean) return
            this.update(this.code.slice(0, index) + clean + this.code.slice(index + clean.length))
            this.focus(index + clean.length)
        },
        input(event, index) {
            if (event.target.value === '')
                this.update(this.code.slice(0, index) + this.code.slice(index + 1))
            else this.insert(event.target.value, index)
            event.target.value = this.code[index] ?? ''
        },
        paste(event, index) {
            this.insert(event.clipboardData.getData('text'), index)
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
