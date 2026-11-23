import { control } from './control.js'

const clamp = (value, min = 0, max = 1) => Math.max(min, Math.min(max, value))
const rectangle = (value) => {
    const source = value ?? { x: 0.1, y: 0.1, width: 0.8, height: 0.8 }
    const width = clamp(Number(source.width), 0.02),
        height = clamp(Number(source.height), 0.02)
    return {
        x: clamp(Number(source.x), 0, 1 - width),
        y: clamp(Number(source.y), 0, 1 - height),
        width,
        height,
    }
}

export function cropper() {
    const state = control()
    return {
        ...state,
        drag: null,
        get surface() {
            return this.$root.querySelector('[data-ui-crop-surface]')
        },
        get frame() {
            return this.$root.querySelector('[data-ui-crop-frame]')
        },
        get rectangle() {
            return rectangle(this.value)
        },
        cropStyle() {
            const rect = this.drag?.next ?? this.rectangle
            return {
                left: `${rect.x * 100}%`,
                top: `${rect.y * 100}%`,
                width: `${rect.width * 100}%`,
                height: `${rect.height * 100}%`,
            }
        },
        init() {
            this.initControl()
            this.refresh()
        },
        refresh() {
            this.cancel()
            this.paint(this.rectangle)
        },
        start(event) {
            if (event.button !== 0 || this.drag || this.config.disabled || this.config.readonly)
                return
            const box = this.surface.getBoundingClientRect()
            if (!box.width || !box.height) return
            const point = {
                x: clamp((event.clientX - box.left) / box.width),
                y: clamp((event.clientY - box.top) / box.height),
            }
            const rect = this.rectangle
            this.drag = {
                pointer: event.pointerId,
                point,
                rect,
                box,
                mode: event.target.closest('[data-ui-crop-resize]')
                    ? 'resize'
                    : event.target.closest('[data-ui-crop-frame]')
                      ? 'move'
                      : 'new',
                next: rect,
            }
            this.surface.setPointerCapture(event.pointerId)
        },
        move(event) {
            if (!this.drag || event.pointerId !== this.drag.pointer) return
            const { box, point, rect, mode } = this.drag
            const x = clamp((event.clientX - box.left) / box.width),
                y = clamp((event.clientY - box.top) / box.height)
            const next =
                mode === 'move'
                    ? { ...rect, x: x - point.x + rect.x, y: y - point.y + rect.y }
                    : mode === 'resize'
                      ? {
                            ...rect,
                            width: Math.min(1 - rect.x, x - rect.x),
                            height: Math.min(1 - rect.y, y - rect.y),
                        }
                      : {
                            x: Math.min(x, point.x),
                            y: Math.min(y, point.y),
                            width: Math.abs(x - point.x),
                            height: Math.abs(y - point.y),
                        }
            this.drag.next = rectangle(next)
            this.paint(this.drag.next)
        },
        paint(rect) {
            Object.assign(this.frame.style, {
                left: `${rect.x * 100}%`,
                top: `${rect.y * 100}%`,
                width: `${rect.width * 100}%`,
                height: `${rect.height * 100}%`,
            })
        },
        finish(event) {
            if (!this.drag || event.pointerId !== this.drag.pointer) return
            const next = this.drag.next
            this.cancel()
            this.write(next, true)
        },
        nudge(x, y) {
            const rect = this.rectangle
            this.write(rectangle({ ...rect, x: rect.x + x * 0.01, y: rect.y + y * 0.01 }), true)
        },
        cancel(event) {
            if (!this.drag || (event && event.pointerId !== this.drag.pointer)) return
            const pointer = this.drag.pointer
            this.drag = null
            if (this.surface.hasPointerCapture(pointer)) this.surface.releasePointerCapture(pointer)
            this.paint(this.rectangle)
        },
        destroy() {
            this.cancel()
            state.destroy.call(this)
        },
    }
}

// Drawing is the explicit canvas exception: Blade still owns controls and messages.
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
                    attributeFilter: ['class', 'style'],
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
