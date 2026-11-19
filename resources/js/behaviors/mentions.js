import { options } from './navigation.js'

const token = (text) => text.match(/(?:^|\s)@([^\s@]*)$/u)?.[1] ?? null

export function mentions(mode) {
    let timer,
        disposed = false
    const releases = []
    return {
        ...options(),
        query: null,
        source() {
            return (mode === 'controlled' ? this.$wire.externalValue : this.$wire.value) ?? ''
        },
        init() {
            this.query = token(this.source())
            const refresh = () => {
                const text = this.source()
                if (this.$refs.input.value !== text) this.$refs.input.value = text
                this.search(text)
            }
            releases.push(
                this.$wire.$watch(mode === 'controlled' ? 'externalValue' : 'value', refresh),
            )
            for (const name of ['disabled', 'readonly'])
                releases.push(this.$wire.$watch(name, () => this.search(this.source())))
        },
        input(event) {
            if (this.$wire.disabled || this.$wire.readonly) return
            const text = event.target.value
            if (mode !== 'controlled') this.$wire.$set('value', text, false)
            this.$dispatch('value-changed', { value: text })
            if (mode === 'controlled') event.target.value = this.source()
            this.search(mode === 'controlled' ? this.source() : text)
        },
        search(text) {
            clearTimeout(timer)
            this.query = token(text)
            if (this.query === null || this.$wire.disabled || this.$wire.readonly) return
            timer = setTimeout(() => {
                if (
                    disposed ||
                    this.query === null ||
                    JSON.parse(this.$root.dataset.mentionQuery) === this.query
                )
                    return
                this.$wire.$commit()
            }, 200)
        },
        destroy() {
            disposed = true
            clearTimeout(timer)
            releases.forEach((release) => release?.())
        },
    }
}
