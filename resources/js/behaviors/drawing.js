import { clamp } from './clamp.js'

export const pixelSize = (size) => Math.max(1, Math.round(size))

export function drawingPoint(event, box) {
    return {
        x: clamp((event.clientX - box.left) / box.width),
        y: clamp((event.clientY - box.top) / box.height),
    }
}

// Keep caller-owned attributes and slots on the element that exposes the public method.
export function mountDrawing(drawing) {
    const host = drawing.$root.parentElement
    const getResult = () => drawing.getResult()
    host.getResult = getResult
    return () => {
        if (host.getResult === getResult) delete host.getResult
    }
}
