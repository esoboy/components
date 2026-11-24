import { control } from '../behaviors/control.js'

import { clamp } from '../behaviors/clamp.js'

export function signature() {
    const state = control()
    let sizeObserver,
        themeObserver,
        pointer = null
    return {
        ...state,
        points: [],
        get canvas() {
            return this.$root.querySelector('canvas')
        },
        init() {
            this.initControl()
            sizeObserver = new ResizeObserver(() => this.paint())
            sizeObserver.observe(this.canvas)
            themeObserver = new MutationObserver(() => this.paint())
            for (let element = this.$root; element; element = element.parentElement) {
                themeObserver.observe(element, {
                    attributes: true,
                    attributeFilter: ['class', 'style', 'data-ui-theme'],
                })
            }
            this.paint()
        },
        refresh() {
            if (this.config.disabled || this.config.readonly) this.cancel()
            this.paint()
        },
        point(event) {
            const box = this.canvas.getBoundingClientRect()
            return {
                x: Math.round(clamp((event.clientX - box.left) / box.width) * 1000) / 1000,
                y: Math.round(clamp((event.clientY - box.top) / box.height) * 1000) / 1000,
            }
        },
        start(event) {
            if (
                event.button !== 0 ||
                pointer !== null ||
                this.config.disabled ||
                this.config.readonly ||
                (this.value?.length ?? 0) >= this.config.maxStrokes
            )
                return
            pointer = event.pointerId
            this.points = [this.point(event)]
            this.canvas.setPointerCapture(pointer)
            this.paint()
        },
        move(event) {
            if (event.pointerId !== pointer || this.points.length >= this.config.maxPoints) return
            const point = this.point(event),
                last = this.points[this.points.length - 1]
            if (Math.hypot(point.x - last.x, point.y - last.y) < 0.003) return
            this.drawStroke([last, point])
            this.points.push(point)
        },
        finish(event) {
            if (event.pointerId !== pointer) return
            const points = [...this.points]
            this.cancel()
            this.write([...(this.value ?? []), points], true)
        },
        clear() {
            this.cancel()
            this.write([], true)
        },
        cancel(event) {
            if (event && event.pointerId !== pointer) return
            const active = pointer
            pointer = null
            this.points = []
            if (active !== null && this.canvas.hasPointerCapture(active))
                this.canvas.releasePointerCapture(active)
            this.paint()
        },
        paint() {
            const box = this.canvas.getBoundingClientRect()
            if (!box.width || !box.height) return
            const ratio = Math.min(devicePixelRatio, 2)
            this.canvas.width = Math.round(box.width * ratio)
            this.canvas.height = Math.round(box.height * ratio)
            this.canvas.getContext('2d').setTransform(ratio, 0, 0, ratio, 0, 0)
            for (const stroke of this.value ?? []) this.drawStroke(stroke)
            if (this.points.length) this.drawStroke(this.points)
        },
        drawStroke(stroke) {
            if (!stroke.length) return
            const ctx = this.canvas.getContext('2d'),
                box = this.canvas.getBoundingClientRect()
            ctx.strokeStyle = ctx.fillStyle = getComputedStyle(this.canvas).color
            ctx.lineWidth = 2
            ctx.lineCap = ctx.lineJoin = 'round'
            ctx.beginPath()
            if (stroke.length === 1) {
                ctx.arc(stroke[0].x * box.width, stroke[0].y * box.height, 1, 0, Math.PI * 2)
                ctx.fill()
            } else {
                ctx.moveTo(stroke[0].x * box.width, stroke[0].y * box.height)
                for (const point of stroke.slice(1))
                    ctx.lineTo(point.x * box.width, point.y * box.height)
                ctx.stroke()
            }
        },
        destroy() {
            this.cancel()
            sizeObserver?.disconnect()
            themeObserver?.disconnect()
            state.destroy.call(this)
        },
    }
}
