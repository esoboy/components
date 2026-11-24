import { control } from '../behaviors/control.js'

import { clamp } from '../behaviors/clamp.js'

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
        },
        refresh() {
            this.cancel()
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
        },
        destroy() {
            this.cancel()
            state.destroy.call(this)
        },
    }
}
