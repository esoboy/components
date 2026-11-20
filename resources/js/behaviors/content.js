import { dialog } from './overlay.js'

const toastState = new WeakMap()

export function toaster(limit = 3) {
    let observer,
        resize,
        queued = false
    return {
        expanded: false,
        hovering: false,
        focused: false,
        windowActive: true,
        previousFocus: null,
        get items() {
            return [...this.$root.querySelectorAll('[data-slot="toast"]')].filter(
                (e) => !e.hidden && e.style.display !== 'none' && !e.hasAttribute('data-ending'),
            )
        },
        shortcut(event) {
            if (event.key !== 'F6' || event.target === this.$root || !this.items.length) return
            event.preventDefault()
            this.previousFocus = document.activeElement
            this.$root.focus({ preventScroll: true })
        },
        navigate(event) {
            if (event.key !== 'Tab') return
            const controls = [
                this.$root,
                ...this.$root.querySelectorAll(
                    'button,a[href],input,select,textarea,[tabindex="0"]',
                ),
            ].filter(
                (e) =>
                    !e.closest('[inert],[data-ending]') &&
                    !e.matches(':disabled') &&
                    e.getClientRects().length,
            )
            const index = controls.indexOf(event.target)
            if (
                (event.shiftKey && index <= 1) ||
                (!event.shiftKey && index === controls.length - 1)
            ) {
                event.preventDefault()
                this.previousFocus?.focus({ preventScroll: true })
            }
        },
        closing(event) {
            if (
                event.target.contains(document.activeElement) &&
                document.activeElement.matches(':focus-visible')
            ) {
                const all = [...this.$root.querySelectorAll('[data-slot="toast"]')],
                    index = all.indexOf(event.target)
                const next =
                    this.items.find((e) => all.indexOf(e) > index) ??
                    this.items.at(-1) ??
                    this.previousFocus
                next?.focus({ preventScroll: true })
            }
            this.schedule()
        },
        windowFocus(value, event) {
            if (event.target !== window) return
            this.windowActive = value
            this.expand()
        },
        init() {
            resize = () => this.schedule()
            observer = new MutationObserver(resize)
            observer.observe(this.$root, { childList: true, subtree: true, characterData: true })
            window.addEventListener('resize', resize)
            this.$nextTick(resize)
        },
        schedule() {
            if (queued) return
            queued = true
            requestAnimationFrame(() => {
                queued = false
                if (this.$root.isConnected) {
                    this.expand()
                    this.measure()
                }
            })
        },
        measure() {
            const items = this.items
            let offset = 0
            const heights = items.map((e) => {
                const height = e.style.height
                e.style.height = 'auto'
                const measured = e.offsetHeight
                e.style.height = height
                return measured
            })
            items.forEach((item, index) => {
                item.dispatchEvent(
                    new CustomEvent('ui-toast-position', {
                        detail: {
                            index,
                            height: heights[index],
                            front: heights[0],
                            offset,
                            expanded: this.expanded,
                            limited: index >= limit,
                        },
                    }),
                )
                offset += heights[index]
            })
        },
        hover(value) {
            this.hovering = value
            this.expand()
        },
        focus(event) {
            if (event.type === 'focusin') this.focused = event.target.matches(':focus-visible')
            else if (!this.$root.contains(event.relatedTarget)) this.focused = false
            this.expand()
        },
        expand() {
            const value = this.hovering || this.focused
            if (
                !value &&
                [...this.$root.querySelectorAll('[data-slot="toast"][data-ending]')].some(
                    (e) => e.style.display !== 'none',
                )
            )
                return
            this.expanded = value
            this.measure()
            this.$root.dispatchEvent(
                new CustomEvent('ui-toast-pause', { detail: value || !this.windowActive }),
            )
        },
        destroy() {
            observer?.disconnect()
            window.removeEventListener('resize', resize)
        },
    }
}

export function toast(duration = 5000) {
    let viewport,
        pauseStack,
        durationObserver,
        closing = 0
    return {
        opened: true,
        visible: true,
        layout: {},
        behind: false,
        expanded: false,
        limited: false,
        stackPaused: false,
        duration,
        remaining: duration,
        timer: null,
        started: 0,
        drag: null,
        get panel() {
            return this.$root
        },
        init() {
            const previous = toastState.get(this.$root)
            toastState.delete(this.$root)
            if (previous)
                Object.assign(this, previous, {
                    duration,
                    remaining: previous.duration === duration ? previous.remaining : duration,
                })
            viewport = this.$root.closest('[data-slot="toaster"]')
            pauseStack = (event) => {
                this.stackPaused = event.detail
                this.stackPaused ? this.pause() : this.resume()
            }
            viewport?.addEventListener('ui-toast-pause', pauseStack)
            durationObserver = new MutationObserver(() => {
                this.pause()
                this.duration = Number(this.$root.dataset.duration)
                this.remaining = this.duration
                this.resume()
            })
            durationObserver.observe(this.$root, {
                attributes: true,
                attributeFilter: ['data-duration'],
            })
            this.resume()
            if (viewport && !previous) {
                this.$root.dataset.starting = ''
                this.$nextTick(() =>
                    requestAnimationFrame(() =>
                        requestAnimationFrame(() => delete this.$root.dataset.starting),
                    ),
                )
            }
        },
        position({ index, height, front, offset, expanded, limited }) {
            this.layout = {
                '--toast-index': index,
                '--toast-height': `${height}px`,
                '--toast-frontmost-height': `${front}px`,
                '--toast-offset-y': `${offset}px`,
            }
            this.behind = index > 0
            this.expanded = expanded
            this.limited = limited
        },
        pause() {
            if (this.timer) {
                clearTimeout(this.timer)
                this.timer = null
                this.remaining = Math.max(0, this.remaining - (performance.now() - this.started))
            }
        },
        resume() {
            if (this.remaining <= 0 || this.timer || !this.opened || this.stackPaused || this.drag)
                return
            this.started = performance.now()
            this.timer = setTimeout(() => this.close(), this.remaining)
        },
        close() {
            if (!this.opened) return
            this.pause()
            this.opened = false
            const attempt = ++closing
            this.$root.dataset.ending = ''
            this.$root.dispatchEvent(new CustomEvent('ui-toast-layout', { bubbles: true }))
            Promise.allSettled(this.$root.getAnimations().map((a) => a.finished)).then(() => {
                if (attempt !== closing || !this.$root.isConnected) return
                this.visible = false
                this.$root.dispatchEvent(new CustomEvent('ui-toast-dismissed', { bubbles: true }))
            })
        },
        startSwipe(event) {
            if (
                !viewport ||
                event.button !== 0 ||
                event.target.closest('button,a,input,textarea,select')
            )
                return
            this.pause()
            this.drag = {
                x: event.clientX,
                y: event.clientY,
                dx: 0,
                dy: 0,
                firstMove: true,
                time: performance.now(),
            }
            event.currentTarget.setPointerCapture(event.pointerId)
        },
        swipe(event) {
            if (!this.drag) return
            if (this.drag.firstMove) {
                this.drag.firstMove = false
                this.drag.x = event.clientX
                this.drag.y = event.clientY
                this.drag.time = performance.now()
            }
            this.drag.dx = event.clientX - this.drag.x
            this.drag.dy = event.clientY - this.drag.y
            this.$root.dataset.swiping = ''
            this.$root.style.setProperty('--toast-swipe-movement-x', `${this.drag.dx}px`)
            this.$root.style.setProperty('--toast-swipe-movement-y', `${this.drag.dy}px`)
        },
        endSwipe(event) {
            if (!this.drag) return
            const { dx, dy, time } = this.drag,
                distance = Math.max(Math.abs(dx), Math.abs(dy))
            this.drag = null
            delete this.$root.dataset.swiping
            if (event.currentTarget.hasPointerCapture(event.pointerId))
                event.currentTarget.releasePointerCapture(event.pointerId)
            if (
                event.type !== 'pointercancel' &&
                (distance > 40 || distance / Math.max(1, performance.now() - time) > 0.5)
            ) {
                this.$root.dataset.swipeDirection =
                    Math.abs(dx) > Math.abs(dy)
                        ? dx > 0
                            ? 'right'
                            : 'left'
                        : dy > 0
                          ? 'down'
                          : 'up'
                this.close()
            } else {
                this.$root.style.removeProperty('--toast-swipe-movement-x')
                this.$root.style.removeProperty('--toast-swipe-movement-y')
                this.resume()
            }
        },
        destroy() {
            closing++
            this.pause()
            // Livewire may move a keyed node through a detached state. Keep browser
            // lifetime state when Alpine initializes that same node again.
            toastState.set(this.$root, {
                opened: this.opened,
                visible: this.visible,
                layout: { ...this.layout },
                behind: this.behind,
                expanded: this.expanded,
                limited: this.limited,
                stackPaused: this.stackPaused,
                duration: this.duration,
                remaining: this.remaining,
            })
            viewport?.removeEventListener('ui-toast-pause', pauseStack)
            durationObserver?.disconnect()
        },
    }
}

export function code() {
    return {
        copied: false,
        timer: null,
        async copy(value = null) {
            const content = this.$root.querySelector('[data-ui-code]')
            const lines = [...content.querySelectorAll('[data-slot="code-block-line-content"]')]
            await navigator.clipboard.writeText(
                value ??
                    (lines.length
                        ? lines
                              .map((line) => (line.textContent === '\n' ? '' : line.textContent))
                              .join('\n')
                        : content.textContent),
            )
            this.copied = true
            clearTimeout(this.timer)
            this.timer = setTimeout(() => (this.copied = false), 2000)
        },
        destroy() {
            clearTimeout(this.timer)
        },
    }
}

export function messageScroller() {
    return {
        anchored: true,
        atStart: true,
        observer: null,
        get viewport() {
            return this.$root.querySelector('[data-slot="message-scroller-viewport"]')
        },
        step(direction) {
            this.viewport.scrollTo({
                top: direction < 0 ? 0 : this.viewport.scrollHeight,
                behavior: matchMedia('(prefers-reduced-motion: reduce)').matches
                    ? 'instant'
                    : 'smooth',
            })
        },
        init() {
            this.observer = new ResizeObserver(() => {
                if (this.anchored) this.viewport.scrollTop = this.viewport.scrollHeight
                this.measure()
            })
            this.observer.observe(this.$root.querySelector('[data-ui-messages]'))
            this.observer.observe(this.viewport)
        },
        measure() {
            this.anchored =
                this.viewport.scrollHeight - this.viewport.scrollTop - this.viewport.clientHeight <
                32
            this.atStart = this.viewport.scrollTop < 32
        },
        destroy() {
            this.observer?.disconnect()
        },
    }
}

export function tour(initial = false) {
    const base = dialog(initial)
    const destroyDialog = base.destroy
    return Object.assign(base, {
        index: 0,
        highlighted: null,
        oldOutline: '',
        init() {
            this.$watch('opened', (value) => {
                this.sync(value)
                if (value) this.$nextTick(() => this.position())
                else this.clearHighlight()
            })
            this.$nextTick(() => {
                this.sync(this.opened)
                this.position()
            })
        },
        step(direction) {
            const count = this.$root.querySelectorAll('[data-slot="tour-step"]').length
            const next = this.index + direction
            if (next >= count) {
                this.close()
                return
            }
            this.index = Math.max(0, next)
            this.$nextTick(() => this.position())
        },
        position() {
            if (!this.opened || !this.panel) return
            this.clearHighlight()
            const step = this.$root.querySelector(
                `[data-slot="tour-step"][data-index="${this.index}"]`,
            )
            let target
            try {
                target = document.querySelector(step?.dataset.target)
            } catch {
                return
            }
            if (!target) return
            this.highlighted = target
            this.oldOutline = target.style.outline
            target.style.outline = '2px solid var(--color-ring)'
            const rect = target.getBoundingClientRect()
            const height = this.panel.offsetHeight
            Object.assign(this.panel.style, {
                margin: '0',
                left: `${Math.max(8, Math.min(rect.left, innerWidth - this.panel.offsetWidth - 8))}px`,
                top: `${Math.max(8, Math.min(rect.bottom + 8, innerHeight - height - 8))}px`,
            })
        },
        clearHighlight() {
            if (this.highlighted) {
                this.highlighted.style.outline = this.oldOutline
                this.highlighted = null
            }
        },
        destroy() {
            this.clearHighlight()
            destroyDialog.call(this)
        },
    })
}
