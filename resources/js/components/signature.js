import { drawingPoint, mountDrawing, pixelSize } from '../behaviors/drawing.js'

function drawStroke(ctx, stroke) {
    const { width, height } = ctx.canvas
    const first = stroke[0]
    ctx.beginPath()
    if (stroke.length === 1) {
        ctx.arc(first.x * width, first.y * height, ctx.lineWidth / 2, 0, Math.PI * 2)
        ctx.fill()
    } else {
        ctx.moveTo(first.x * width, first.y * height)
        for (const point of stroke) ctx.lineTo(point.x * width, point.y * height)
        ctx.stroke()
    }
}

export function signature(initial, maxStrokes, maxPoints) {
    let strokes = [...initial]
    let points = []
    let pointer = null
    let ratio = 1
    let canvas, sizeObserver, themeObserver, disconnect
    function render(target, draft = []) {
        const ctx = target.getContext('2d')
        ctx.strokeStyle = ctx.fillStyle = getComputedStyle(canvas).color
        ctx.lineWidth = 2 * ratio
        ctx.lineCap = ctx.lineJoin = 'round'
        ctx.clearRect(0, 0, target.width, target.height)
        for (const stroke of strokes) drawStroke(ctx, stroke)
        if (draft.length) drawStroke(ctx, draft)
    }
    return {
        init() {
            canvas = this.$root.querySelector('canvas')
            disconnect = mountDrawing(this)
            const paint = this.paint.bind(this)
            sizeObserver = new ResizeObserver(paint)
            sizeObserver.observe(canvas)
            themeObserver = new MutationObserver(paint)
            for (let element = this.$root; element; element = element.parentElement) {
                themeObserver.observe(element, {
                    attributes: true,
                    attributeFilter: ['class', 'style', 'data-ui-theme'],
                })
            }
            paint()
        },
        reset(value = initial) {
            strokes = [...value]
            this.cancel()
        },
        async getResult() {
            if (!strokes.length) return null
            const output = new OffscreenCanvas(canvas.width, canvas.height)
            render(output)
            return output.convertToBlob()
        },
        start(event) {
            if (
                event.button !== 0 ||
                pointer !== null ||
                this.$root.disabled ||
                strokes.length >= maxStrokes
            )
                return
            pointer = event.pointerId
            points = [drawingPoint(event, canvas.getBoundingClientRect())]
            canvas.setPointerCapture(pointer)
            drawStroke(canvas.getContext('2d'), points)
        },
        move(event) {
            if (event.pointerId !== pointer || points.length >= maxPoints) return
            const point = drawingPoint(event, canvas.getBoundingClientRect())
            const last = points[points.length - 1]
            if (Math.hypot(point.x - last.x, point.y - last.y) < 0.003) return
            drawStroke(canvas.getContext('2d'), [last, point])
            points.push(point)
        },
        finish(event) {
            if (event.pointerId !== pointer) return
            strokes.push(points)
            this.cancel()
        },
        cancel(event) {
            if (event && event.pointerId !== pointer) return
            const active = pointer
            pointer = null
            points = []
            if (active !== null && canvas.hasPointerCapture(active))
                canvas.releasePointerCapture(active)
            this.paint()
        },
        paint() {
            const box = canvas.getBoundingClientRect()
            if (!box.width || !box.height) return
            ratio = Math.min(devicePixelRatio, 2)
            const width = pixelSize(box.width * ratio)
            const height = pixelSize(box.height * ratio)
            if (canvas.width !== width) canvas.width = width
            if (canvas.height !== height) canvas.height = height
            render(canvas, points)
        },
        destroy() {
            sizeObserver.disconnect()
            themeObserver.disconnect()
            disconnect()
        },
    }
}
