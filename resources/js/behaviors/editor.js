import { control } from './control.js'

// The Web Component owns rich editing; this adapter only connects its value/lifecycle.
export function editor() {
    const state = control()
    let surface,
        lastWritten,
        pendingChange = false
    return {
        ...state,
        ready: false,
        failed: false,
        formValue: '',
        async init() {
            let rememberInitial
            this.initControl(
                new Promise((resolve) => {
                    rememberInitial = resolve
                }),
            )
            const initial = this.config.value ?? ''
            surface = this.$refs.surface
            const initialized = () => {
                rememberInitial(surface.value)
                lastWritten = initial
                this.ready = true
                this.refresh()
            }
            await customElements.whenDefined('esoboy-editor')
            if (this.isDisposed()) return
            surface.value = initial
            if (surface.editor) initialized()
            else this.listen(surface, 'editor-ready', initialized)
        },
        edited(value) {
            pendingChange = true
            lastWritten = this.config.mode === 'controlled' ? undefined : value
            this.write(value)
        },
        refresh() {
            if (this.isDisposed() || !this.ready) return
            const source = this.value ?? ''
            if (source !== lastWritten) surface.value = source
            this.formValue = surface.value
            const controlled = this.config.mode === 'controlled'
            lastWritten = controlled ? source : this.formValue
            if (!controlled && this.value !== this.formValue) {
                this.value = this.formValue
                this.signal('input')
            }
        },
        blurred(event) {
            if (!this.$root.contains(event.relatedTarget) && pendingChange) {
                pendingChange = false
                this.signal('change')
            }
            state.blurred.call(this, event)
        },
    }
}
