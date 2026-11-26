import { enabled } from '../behaviors/keyboard.js'
import { focusInPlace } from '../behaviors/overlay.js'

const toastState = new WeakMap()

export function toaster(limit = 3) {
    let previousFocus = null
    let observer,
        queued = false
    return {
        expanded: false,
        hovering: false,
        focused: false,

        get items() {
            return [...this.$root.querySelectorAll('.ui-toast')].filter(
                (e) => !e.hidden && e.style.display !== 'none' && !e.hasAttribute('data-ending'),
            )
        },
        shortcut(event) {
            if (this.$root.contains(document.activeElement) || !this.items.length) return
            event.preventDefault()
            previousFocus = document.activeElement
            focusInPlace(this.$root)
        },
        navigate(event) {
            if (!previousFocus?.isConnected) return
            const controls = [
                this.$root,
                ...enabled(this.$root, 'button,a[href],input,select,textarea,[tabindex="0"]'),
            ].filter((e) => !e.closest('[data-ending]'))
            const index = controls.indexOf(event.target)
            if (
                (event.shiftKey && index <= 1) ||
                (!event.shiftKey && index === controls.length - 1)
            ) {
                event.preventDefault()
                focusInPlace(previousFocus)
            }
        },
        closing(event) {
            if (
                event.target.contains(document.activeElement) &&
                document.activeElement.matches(':focus-visible')
            ) {
                const all = [...this.$root.querySelectorAll('.ui-toast')],
                    index = all.indexOf(event.target)
                const next =
                    this.items.find((e) => all.indexOf(e) > index) ??
                    this.items.at(-1) ??
                    previousFocus
                focusInPlace(next)
            }
            this.schedule()
        },
        init() {
            const layout = () => this.schedule()
            observer = new MutationObserver(layout)
            observer.observe(this.$root, { childList: true, subtree: true, characterData: true })
            this.$nextTick(layout)
        },
        schedule() {
            if (queued) return
            queued = true
            requestAnimationFrame(() => {
                queued = false
                if (this.$root.isConnected) this.expand()
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
        leave(next) {
            if (this.$root.contains(next)) return
            previousFocus = null
            this.focused = false
            this.expand()
        },
        expand() {
            this.expanded =
                this.hovering ||
                this.focused ||
                (this.expanded &&
                    [...this.$root.querySelectorAll('.ui-toast[data-ending]')].some(
                        (e) => e.style.display !== 'none',
                    ))
            this.measure()
            this.$root.dispatchEvent(
                new CustomEvent('ui-toast-pause', {
                    detail: this.expanded || !document.hasFocus(),
                }),
            )
        },
        destroy() {
            observer?.disconnect()
        },
    }
}

export function toast(duration = 5000) {
    let drag = null
    let timer = null
    let started = 0
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
            viewport = this.$root.closest('.ui-toaster')
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
            if (!this.opened && this.visible) this.finishClose()
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
            if (timer) {
                clearTimeout(timer)
                timer = null
                this.remaining = Math.max(0, this.remaining - (performance.now() - started))
            }
        },
        resume() {
            if (this.remaining <= 0 || timer || !this.opened || this.stackPaused || drag) return
            started = performance.now()
            timer = setTimeout(() => this.close(), this.remaining)
        },
        close() {
            if (!this.opened) return
            this.pause()
            this.opened = false
            this.$dispatch('ui-toast-layout')
            this.finishClose()
        },
        finishClose() {
            const attempt = ++closing
            this.$root.dataset.ending = ''
            Promise.allSettled(this.$root.getAnimations().map((a) => a.finished)).then(() => {
                if (attempt !== closing || !this.$root.isConnected) return
                this.visible = false
                this.$dispatch('ui-toast-dismissed')
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
            drag = {
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
            if (!drag) return
            if (drag.firstMove) {
                drag.firstMove = false
                drag.x = event.clientX
                drag.y = event.clientY
                drag.time = performance.now()
            }
            drag.dx = event.clientX - drag.x
            drag.dy = event.clientY - drag.y
            this.$root.dataset.swiping = ''
            this.$root.style.setProperty('--toast-swipe-movement-x', `${drag.dx}px`)
            this.$root.style.setProperty('--toast-swipe-movement-y', `${drag.dy}px`)
        },
        endSwipe(event) {
            if (!drag) return
            const { dx, dy, time } = drag,
                distance = Math.max(Math.abs(dx), Math.abs(dy))
            drag = null
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
