import { countdowns, format } from '../support/countdown.js'

export default verification => ({
    end: 0, timer: null, finished: true, remaining: 0, started: false,
    init() {
        const name = this.$el.dataset.name
        this.started = countdowns.has(name)
        this.end = countdowns.get(name) ?? (verification ? 0 : Date.now() + Number(this.$el.dataset.remaining))
        this.finished = this.end <= Date.now()
        if (name && !verification) countdowns.set(name, this.end)
        this.tick()
    },
    destroy() { clearTimeout(this.timer) },
    start(detail) {
        if (detail.name !== this.$el.dataset.name) return
        this.end = Date.now() + Number(detail.seconds ?? this.$el.dataset.seconds ?? 60) * 1000
        countdowns.set(detail.name, this.end)
        this.finished = false; this.started = true; this.tick()
    },
    tick() {
        clearTimeout(this.timer)
        this.remaining = Math.max(0, this.end - Date.now())
        if (verification) {
            if (!this.started) return
            const label = this.$el.querySelector('[data-verification-code-label]')
            if (label) label.textContent = this.remaining ? this.$el.dataset.labelCountdown.replace(':seconds', Math.ceil(this.remaining / 1000)) : this.$el.dataset.labelResend
        } else this.$el.textContent = format(this.remaining, this.$el.dataset.format ?? 'HH:mm:ss')
        if (this.remaining > 0) this.timer = setTimeout(() => this.tick(), this.remaining % 1000 || 1000)
        else if (!this.finished) { this.finished = true; this.$dispatch('finish') }
    },
    block(event) { if (verification && this.remaining > 0) { event.preventDefault(); event.stopImmediatePropagation() } },
})
