const copyValue = (value) => JSON.parse(JSON.stringify(value))

// A native input buffer, connected to its owner using public wire:model events.
// Blade owns configuration and markup; no child component or extra server state.
export function control() {
    let observer, initial, unwatch
    const events = new AbortController()
    const isDisposed = () => events.signal.aborted
    return {
        isDisposed,
        value: null,
        config: {},
        listen(element, type, handler, capture = false) {
            element?.addEventListener(type, handler, { capture, signal: events.signal })
        },
        initControl(initialOutput) {
            const readConfig = () => JSON.parse(this.$root.dataset.uiControl)
            this.config = readConfig()
            initial = copyValue(this.config.value)
            this.value = this.config.value
            if (this.config.mode === 'bound') {
                this.value = this.$wire.$get(this.config.model)
                unwatch = this.$wire.$watch(this.config.model, (value) => {
                    this.value = value
                    this.refresh()
                })
            }
            observer = new MutationObserver(() => {
                this.config = readConfig()
                if (this.config.mode === 'controlled') this.value = this.config.value
                this.$nextTick(() => {
                    if (!isDisposed()) this.refresh()
                })
            })
            observer.observe(this.$root, { attributes: true, attributeFilter: ['data-ui-control'] })
            // A field can change its native form owner without remounting the control.
            const onForm = (type, handler) =>
                this.listen(
                    this.$root.getRootNode(),
                    type,
                    (event) => {
                        const field = this.$root.querySelector('input[form]')
                        const form = field ? field.form : this.$root.closest('form')
                        if (event.target === form) handler(event)
                    },
                    true,
                )
            const reset = (event) =>
                this.$nextTick(async () => {
                    const value = initialOutput ? await initialOutput : initial
                    if (isDisposed() || event.defaultPrevented) return
                    if (this.config.mode !== 'controlled') this.value = copyValue(initial)
                    this.refresh()
                    this.signal('input')
                    this.signal('change')
                    this.signal('blur')
                    this.$dispatch('value-changed', { value: copyValue(value) })
                })
            // A composite control's blur modifier must also flush on form submit.
            onForm('reset', reset)
            onForm('submit', () => this.signal('blur'))
        },
        write(value, committed = false) {
            if (this.config.disabled || this.config.readonly) return
            if (this.config.mode !== 'controlled') {
                this.value = value
                this.signal('input')
                if (committed) this.signal('change')
            }
            this.$dispatch('value-changed', { value })
            this.refresh()
        },
        signal(type) {
            if (this.config.mode !== 'bound') return
            this.$root.value = this.value
            const event = new CustomEvent(type, { bubbles: true, detail: this.value })
            if (type === 'keydown') Object.defineProperty(event, 'key', { value: 'Enter' })
            this.$root.dispatchEvent(event)
        },
        changed(event) {
            if (event.target !== this.$root) this.signal('change')
        },
        blurred(event) {
            if (!this.$root.contains(event.relatedTarget)) this.signal('blur')
        },
        entered(event) {
            if (event.target !== this.$root) this.signal('keydown')
        },
        destroy() {
            events.abort()
            observer?.disconnect()
            unwatch?.()
        },
    }
}
