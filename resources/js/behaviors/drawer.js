import { dialog } from './overlay.js'

const drawers = new Set()
function updateStack() {
    const active = [...drawers].filter((panel) => panel.open && !panel.hasAttribute('data-ending'))
    for (const panel of drawers) {
        const children = active.filter((child) => child !== panel && panel.contains(child))
        panel.style.setProperty('--ui-nested-drawers', children.length)
        panel.style.setProperty(
            '--ui-front-height',
            `${children.at(-1)?.offsetHeight ?? panel.offsetHeight}px`,
        )
        panel.toggleAttribute('data-nested-open', children.length > 0)
    }
}

export function drawer(
    initial = false,
    modal = true,
    snapPoints = [],
    initialSnapPoint = null,
    sequential = false,
) {
    const base = dialog(initial, modal)
    let resize,
        observer,
        observedPanel,
        escape,
        previousOffset = 0
    function untrack() {
        drawers.delete(observedPanel)
        observer?.disconnect()
        observedPanel = null
    }
    return Object.defineProperties(
        Object.create(Object.getPrototypeOf(base), Object.getOwnPropertyDescriptors(base)),
        Object.getOwnPropertyDescriptors({
            drag: null,
            snapPoint: initialSnapPoint ?? snapPoints[0] ?? null,
            init() {
                base.init.call(this)
                resize = () => this.positionSnap()
                window.addEventListener('resize', resize)
                observer = new ResizeObserver(() => {
                    this.positionSnap()
                    updateStack()
                })
                escape = (event) => {
                    if (
                        !modal &&
                        event.key === 'Escape' &&
                        !event.defaultPrevented &&
                        [...drawers].at(-1) === this.panel
                    ) {
                        event.preventDefault()
                        this.close()
                    }
                }
                document.addEventListener('keydown', escape)
            },
            get snapOffsets() {
                const height = this.panel.offsetHeight,
                    viewport = document.documentElement.clientHeight
                const rem = parseFloat(getComputedStyle(document.documentElement).fontSize)
                return snapPoints.map((point) => {
                    const requested =
                        typeof point === 'number'
                            ? point <= 1
                                ? point * viewport
                                : point
                            : parseFloat(point) * (point.endsWith('rem') ? rem : 1)
                    return Math.max(0, height - Math.min(height, viewport, requested))
                })
            },
            positionSnap() {
                if (!snapPoints.length || !this.panel?.open) return
                this.panel.dataset.snapPoints = ''
                const offset = this.snapOffsets[snapPoints.indexOf(this.snapPoint)] ?? 0
                this.panel.style.setProperty(
                    '--ui-snap-offset',
                    `${this.panel.dataset.side === 'top' ? -offset : offset}px`,
                )
                const offsets = this.snapOffsets,
                    range = Math.max(...offsets) - Math.min(...offsets)
                this.panel.style.setProperty(
                    '--ui-drawer-progress',
                    range > 0 ? (offset - Math.min(...offsets)) / range : 0,
                )
            },
            sync(value) {
                const panel = this.panel
                const wasOpen = panel?.open
                base.sync.call(this, value)
                if (value && panel?.open) {
                    if (panel !== observedPanel) {
                        untrack()
                        observedPanel = panel
                        observer.observe(panel)
                        drawers.add(panel)
                    }
                    if (!wasOpen) panel.focus({ preventScroll: true })
                    panel.style.removeProperty('--ui-swipe-duration')
                    this.positionSnap()
                }
                updateStack()
            },
            startDrag(event) {
                if (event.pointerType === 'touch' && !event.nativeTouch) return
                if (event.button !== 0 || this.drag || this.panel.hasAttribute('data-nested-open'))
                    return
                if (event.target.closest('.ui-drawer-content') !== this.panel) return
                const content = event.target.closest('.ui-drawer-viewport')
                if (
                    event.pointerType !== 'touch' &&
                    content?.closest('.ui-drawer-content') === this.panel
                )
                    return
                if (event.target.closest('button,a,input,textarea,select,[contenteditable=true]'))
                    return
                const side = this.panel.dataset.side,
                    vertical = ['top', 'bottom'].includes(side)
                let scrollTarget = null
                for (
                    let node = event.target;
                    node && node !== this.panel;
                    node = node.parentElement
                ) {
                    const style = getComputedStyle(node),
                        overflow = vertical ? style.overflowY : style.overflowX
                    if (
                        /auto|scroll/.test(overflow) &&
                        (vertical
                            ? node.scrollHeight > node.clientHeight
                            : node.scrollWidth > node.clientWidth)
                    ) {
                        scrollTarget = node
                        break
                    }
                }
                this.drag = {
                    side,
                    vertical,
                    touch: event.nativeTouch === true,
                    scrollTarget,
                    firstMove: true,
                    start: vertical ? event.clientY : event.clientX,
                    offset: 0,
                    time: performance.now(),
                    lastTime: performance.now(),
                    velocity: 0,
                }
                previousOffset = 0
                this.panel.dataset.dragging = 'true'
                if (event.pointerId !== undefined)
                    event.currentTarget.setPointerCapture(event.pointerId)
            },
            touchStart(event) {
                if (event.touches.length !== 1) return
                const touch = event.touches[0]
                this.startDrag({
                    button: 0,
                    pointerType: 'touch',
                    nativeTouch: true,
                    target: event.target,
                    currentTarget: event.currentTarget,
                    clientX: touch.clientX,
                    clientY: touch.clientY,
                })
            },
            touchMove(event) {
                if (!this.drag?.touch || event.touches.length !== 1) return
                const touch = event.touches[0],
                    { scrollTarget, vertical, side, start } = this.drag
                if (scrollTarget) {
                    const delta = (vertical ? touch.clientY : touch.clientX) - start
                    const offset = vertical ? scrollTarget.scrollTop : scrollTarget.scrollLeft
                    const max = vertical
                        ? scrollTarget.scrollHeight - scrollTarget.clientHeight
                        : scrollTarget.scrollWidth - scrollTarget.clientWidth
                    const fromStart = side === 'bottom' || side === 'right'
                    if (fromStart ? delta <= 0 || offset > 0 : delta >= 0 || offset < max) return
                    this.drag.firstMove = false
                }
                if (event.cancelable) event.preventDefault()
                event.stopPropagation()
                this.dragging({
                    type: 'touchmove',
                    clientX: touch.clientX,
                    clientY: touch.clientY,
                    preventDefault() {},
                })
            },
            nestedDrag(progress, active) {
                for (const panel of drawers) {
                    if (panel === this.panel || !panel.contains(this.panel)) continue
                    panel.toggleAttribute('data-nested-swiping', active)
                    panel.style.setProperty('--ui-stack-progress', progress)
                }
            },
            dragging(event) {
                if (!this.drag) return
                if (this.drag.touch && event.type.startsWith('pointer')) return
                if (this.drag.firstMove) {
                    this.drag.firstMove = false
                    this.drag.start = this.drag.vertical ? event.clientY : event.clientX
                    this.drag.time = performance.now()
                }
                event.preventDefault()
                const sign = ['top', 'left'].includes(this.drag.side) ? -1 : 1
                const raw =
                    ((this.drag.vertical ? event.clientY : event.clientX) - this.drag.start) * sign
                const baseOffset = this.snapOffsets[snapPoints.indexOf(this.snapPoint)] ?? 0
                const offset = snapPoints.length
                    ? baseOffset + raw < 0
                        ? -Math.sqrt(-baseOffset - raw) - baseOffset
                        : raw
                    : Math.max(0, raw)
                const now = performance.now()
                this.drag.velocity =
                    (offset - previousOffset) / Math.max(1, now - this.drag.lastTime)
                this.drag.lastTime = now
                this.drag.offset = offset
                previousOffset = offset
                this.panel.style.setProperty(
                    this.drag.vertical ? '--ui-drag-y' : '--ui-drag-x',
                    `${offset * sign}px`,
                )
                const size = this.drag.vertical ? this.panel.offsetHeight : this.panel.offsetWidth
                const offsets = this.snapOffsets,
                    range = Math.max(...offsets) - Math.min(...offsets)
                const progress = Math.max(
                    0,
                    Math.min(
                        1,
                        snapPoints.length > 1 && range > 0
                            ? (baseOffset + offset - Math.min(...offsets)) / range
                            : offset / size,
                    ),
                )
                this.panel.style.setProperty('--ui-drawer-progress', progress)
                this.nestedDrag(progress, true)
            },
            endDrag(event) {
                if (!this.drag) return
                if (this.drag.touch && event.type.startsWith('pointer')) return
                const { vertical, offset, time, velocity: recentVelocity, lastTime } = this.drag
                const size = vertical ? this.panel.offsetHeight : this.panel.offsetWidth
                const velocity = performance.now() - lastTime < 80 ? recentVelocity : 0
                const average = offset / Math.max(1, performance.now() - time)
                const cancelled = event.type === 'pointercancel' || event.type === 'touchcancel'
                let dismiss =
                    !cancelled &&
                    offset > 0 &&
                    (average >= 0.5 || offset > Math.max(10, size * 0.5))
                if (snapPoints.length && !cancelled) {
                    const offsets = this.snapOffsets,
                        current = offsets[snapPoints.indexOf(this.snapPoint)] ?? 0
                    const projected =
                        current +
                        offset +
                        (Math.abs(velocity) >= 0.5 ? Math.max(-4, Math.min(4, velocity)) * 300 : 0)
                    const candidates = [...offsets, size]
                    let target = candidates.reduce((best, value) =>
                        Math.abs(value - projected) < Math.abs(best - projected) ? value : best,
                    )
                    if (sequential) {
                        const ordered = [...candidates].sort((a, b) => a - b),
                            index = ordered.indexOf(current),
                            direction = Math.sign(target - current)
                        target =
                            ordered[Math.max(0, Math.min(ordered.length - 1, index + direction))]
                    }
                    dismiss = target === size
                    if (!dismiss) this.snapPoint = snapPoints[offsets.indexOf(target)]
                }
                this.drag = null
                delete this.panel.dataset.dragging
                if (dismiss && velocity > 0.2) {
                    const duration = Math.max(
                        80,
                        Math.min(360, Math.max(0, size - offset) / Math.min(4, velocity)),
                    )
                    this.panel.style.setProperty(
                        '--ui-swipe-duration',
                        `${400 * (0.1 + ((duration - 80) / 280) * 0.9)}ms`,
                    )
                }
                this.panel.style.removeProperty('--ui-drag-x')
                this.panel.style.removeProperty('--ui-drag-y')
                this.panel.style.removeProperty('--ui-drawer-progress')
                this.nestedDrag(0, false)
                if (
                    event.pointerId !== undefined &&
                    event.currentTarget.hasPointerCapture(event.pointerId)
                )
                    event.currentTarget.releasePointerCapture(event.pointerId)
                dismiss ? this.close() : this.positionSnap()
            },
            closed() {
                if (this.panel?.open) return
                base.closed.call(this)
                untrack()
                updateStack()
            },
            destroy() {
                window.removeEventListener('resize', resize)
                untrack()
                document.removeEventListener('keydown', escape)
                base.destroy.call(this)
                updateStack()
            },
        }),
    )
}
