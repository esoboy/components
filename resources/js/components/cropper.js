import { clamp } from '../behaviors/clamp.js'
import { drawingPoint, mountDrawing, pixelSize } from '../behaviors/drawing.js'

const rectangle = ({ x, y, width, height }) => {
    width = clamp(width, 0.02)
    height = clamp(height, 0.02)
    return {
        x: clamp(x, 0, 1 - width),
        y: clamp(y, 0, 1 - height),
        width,
        height,
    }
}

export function cropper(initial) {
    let surface, disconnect
    return {
        rect: initial,
        drag: null,
        get frame() {
            return this.drag?.next ?? this.rect
        },
        init() {
            surface = this.$root.firstElementChild
            disconnect = mountDrawing(this)
        },
        reset() {
            this.cancel()
            this.rect = initial
        },
        async getResult() {
            const { x, y, width, height } = this.rect
            const image = surface.querySelector('img')
            image.loading = 'eager'
            await image.decode()
            const { naturalWidth, naturalHeight } = image
            const output = new OffscreenCanvas(
                pixelSize(width * naturalWidth),
                pixelSize(height * naturalHeight),
            )
            const left = Math.floor(x * naturalWidth)
            const top = Math.floor(y * naturalHeight)
            output.getContext('2d').drawImage(image, -left, -top, naturalWidth, naturalHeight)
            return output.convertToBlob()
        },
        start(event) {
            if (event.button !== 0 || this.drag || this.$root.disabled) return
            const box = surface.getBoundingClientRect()
            if (!box.width || !box.height) return
            this.drag = {
                pointer: event.pointerId,
                point: drawingPoint(event, box),
                box,
                mode: event.target.dataset.cropMode,
                next: this.rect,
            }
            surface.setPointerCapture(event.pointerId)
        },
        move(event) {
            if (!this.drag || event.pointerId !== this.drag.pointer) return
            const { box, point, mode } = this.drag
            const { x, y } = drawingPoint(event, box)
            const rect = this.rect
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
            this.rect = this.drag.next
            this.cancel()
        },
        nudge(x, y) {
            if (this.drag || this.$root.disabled) return
            const rect = this.rect
            this.rect = rectangle({ ...rect, x: rect.x + x * 0.01, y: rect.y + y * 0.01 })
        },
        cancel(event) {
            if (!this.drag || (event && event.pointerId !== this.drag.pointer)) return
            const pointer = this.drag.pointer
            this.drag = null
            if (surface.hasPointerCapture(pointer)) surface.releasePointerCapture(pointer)
        },
        destroy() {
            this.cancel()
            disconnect()
        },
    }
}
