import boundValue from './value.js'

const clamp = number => Math.min(1, Math.max(0, number))
export function parseHex(text) {
    const hex = String(text ?? '').trim().replace(/^#/, '').toLowerCase()
    const full = hex.length === 3 ? [...hex].map(digit => digit + digit).join('') : hex
    return /^[0-9a-f]{6}$/.test(full) ? `#${full}` : null
}
export function hexToHsv(hex) {
    const number = parseInt(hex.slice(1), 16)
    const [r, g, b] = [number >> 16, number >> 8, number].map(channel => (channel & 255) / 255)
    const v = Math.max(r, g, b), delta = v - Math.min(r, g, b)
    const hue = !delta ? 0 : v === r ? ((g - b) / delta) % 6 : v === g ? (b - r) / delta + 2 : (r - g) / delta + 4
    return { h: (hue * 60 + 360) % 360, s: v ? delta / v : 0, v }
}
export function hsvToHex({ h, s, v }) {
    const channel = n => { const k = (n + h / 60) % 6; return v - v * s * Math.max(0, Math.min(k, 4 - k, 1)) }
    return `#${[channel(5), channel(3), channel(1)].map(c => Math.round(c * 255).toString(16).padStart(2, '0')).join('')}`
}
export default binding => Object.defineProperties(boundValue(binding), Object.getOwnPropertyDescriptors({
    hue: 0, dragging: false,
    get hsv() { const hsv = hexToHsv(parseHex(this.value) ?? '#000000'); if (!hsv.s || hsv.h === 0 && this.hue === 360) hsv.h = this.hue; return hsv },
    paint() {
        const color = parseHex(this.value), hsv = this.hsv
        if (hsv.s) this.hue = hsv.h
        this.$el.style.setProperty('--color-picker-hue', hsv.h)
        const thumb = this.$el.querySelector('[data-slot="color-picker-thumb"]')
        if (thumb) { thumb.style.left = `${hsv.s * 100}%`; thumb.style.top = `${(1 - hsv.v) * 100}%`; thumb.style.setProperty('--color-picker-color', hsvToHex(hsv)) }
        this.$el.querySelector('[data-slot="color-picker-area"]')?.setAttribute('aria-valuetext', `${Math.round(hsv.s * 100)}%, ${Math.round(hsv.v * 100)}%`)
        this.$el.querySelector('[data-slot="color-picker-swatch"]')?.style.setProperty('--color-picker-color', color ?? 'transparent')
        const label = this.$el.querySelector('[data-slot="color-picker-value"]')
        if (label) label.textContent = color ?? this.$el.dataset.placeholder ?? ''
        const trigger = this.$el.querySelector('[data-color-picker-trigger]')
        if (trigger) trigger.dataset.empty = String(!color)
        for (const swatch of this.$el.querySelectorAll('[data-color-picker-swatch]')) swatch.setAttribute('aria-pressed', String(parseHex(swatch.dataset.colorPickerSwatch) === color))
        const input = this.$el.querySelector('[data-color-picker-input]')
        if (input && input !== document.activeElement) input.value = color ?? ''
        const range = this.$el.querySelector('[data-color-picker-hue] input[type="range"]')
        if (range && Number(range.value) !== Math.round(hsv.h)) { range.value = Math.round(hsv.h); this.$nextTick(() => window.Alpine.evaluate(range.closest('[data-slot="slider"]'), 'paint()')) }
    },
    commit(value) { this.value = value; this.changed() },
    hex(text) {
        const value = parseHex(text)
        if (String(text).trim() !== '' && !value) return
        this.commit(value)
    },
    color(changes) { this.commit(hsvToHex({ ...this.hsv, ...changes })) },
    input(event) {
        if (event.target.matches('[data-color-picker-hue] input[type="range"]')) { this.hue = Number(event.target.value); this.color({ h: this.hue }) }
    },
    change(event) { if (event.target.matches('[data-color-picker-input]')) this.hex(event.target.value) },
    click(event) {
        const swatch = event.target.closest('[data-color-picker-swatch]')
        if (swatch) this.hex(swatch.dataset.colorPickerSwatch)
        if (event.target.closest('[data-color-picker-eyedropper]')) this.pick()
    },
    async pick() { try { this.hex((await new window.EyeDropper().open()).sRGBHex) } catch {} },
    point(area, event) { const box = area.getBoundingClientRect(); this.color({ s: clamp((event.clientX - box.left) / (box.width || 1)), v: clamp(1 - (event.clientY - box.top) / (box.height || 1)) }) },
    start(event) {
        const area = event.target.closest('[data-slot="color-picker-area"]')
        if (!area || event.button !== 0) return
        event.preventDefault(); area.focus(); area.setPointerCapture(event.pointerId); this.dragging = true; this.point(area, event)
    },
    move(event) { const area = event.target.closest('[data-slot="color-picker-area"]'); if (area && this.dragging) this.point(area, event) },
    key(event) {
        if (event.target.matches('[data-color-picker-input]') && event.key === 'Enter') { event.preventDefault(); this.hex(event.target.value) }
        if (!event.target.matches('[data-slot="color-picker-area"]')) return
        const step = event.shiftKey ? .1 : .01, move = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] }[event.key]
        if (move) { event.preventDefault(); this.color({ s: clamp(this.hsv.s + move[0]), v: clamp(this.hsv.v + move[1]) }) }
    },
}))
