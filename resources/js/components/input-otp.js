import { clamp } from '../behaviors/clamp.js'

export function otp() {
    let initial, stopReset
    return {
        get config() {
            return JSON.parse(this.$root.dataset.uiControl)
        },
        get code() {
            return this.$refs.field.value
        },
        get slots() {
            return this.$root.querySelectorAll('[data-otp-index]')
        },
        init() {
            initial = this.config.value ?? ''
            const reset = (event) => {
                if (event.target !== this.$refs.field.form) return
                queueMicrotask(() => {
                    if (event.defaultPrevented || !this.$root.isConnected) return
                    this.update(initial)
                    this.signal('blur')
                })
            }
            const document = this.$root.getRootNode()
            document.addEventListener('reset', reset, true)
            stopReset = () => document.removeEventListener('reset', reset, true)
            this.$nextTick(() => this.refresh())
        },
        destroy() {
            stopReset()
        },
        signal(type) {
            const event =
                type === 'keydown'
                    ? new KeyboardEvent(type, { key: 'Enter' })
                    : new Event(type, { bubbles: type !== 'blur' })
            this.$refs.field.dispatchEvent(event)
        },
        refresh() {
            const config = this.config
            if (config.mode === 'controlled') this.$refs.field.value = config.value ?? ''
            const normalized = this.code
                .replace(config.pattern === 'numeric' ? /[^0-9]/g : /[^a-z0-9]/gi, '')
                .slice(0, config.length)
            if (this.code !== normalized) {
                this.$refs.field.value = normalized
                this.signal('input')
                this.signal('change')
            }
            this.slots.forEach((input, index) => {
                input.value = this.code[index] ?? ''
            })
        },
        focus(index) {
            const slots = this.slots
            slots[clamp(index, 0, slots.length - 1)]?.focus()
        },
        finishEdit() {
            if (!this.editing) return
            this.editing = false
            this.signal('change')
        },
        update(value) {
            const config = this.config
            const next = value.slice(0, config.length)
            if (config.mode !== 'controlled') {
                this.editing = this.$refs.field.value !== next || this.editing
                this.$refs.field.value = next
                this.signal('input')
            }
            this.refresh()
            this.$dispatch('value-changed', { value: next })
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
            if (
                event.key === 'Backspace' &&
                !event.target.value &&
                index > 0 &&
                !event.target.matches(':disabled,[readonly]')
            ) {
                event.preventDefault()
                this.update(this.code.slice(0, index - 1) + this.code.slice(index))
                this.focus(index - 1)
            }
        },
    }
}
