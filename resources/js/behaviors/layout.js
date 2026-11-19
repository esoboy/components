import EmblaCarousel from 'embla-carousel'

export function disclosure(initial = false) {
    let transition = 0
    return {
        opened: initial,
        init() {
            this.$watch('opened', (value) => {
                const attempt = ++transition,
                    panel = this.panel
                if (!panel) return
                delete panel.dataset.settled
                if (!value) {
                    delete panel.dataset.startingStyle
                    if (panel.dataset.slot === 'trace-collapsible-content') {
                        this.$nextTick(() => {
                            if (attempt !== transition) return
                            panel.style.display = 'block'
                            panel.style.height = `${panel.scrollHeight}px`
                            panel.style.transition = 'none'
                            getComputedStyle(panel).height
                            requestAnimationFrame(() => {
                                if (attempt !== transition) return
                                panel.style.removeProperty('transition')
                                panel.style.removeProperty('height')
                                panel.style.display = 'none'
                            })
                        })
                    }
                    return
                }
                panel.style.removeProperty('transition')
                panel.style.removeProperty('height')
                panel.dataset.startingStyle = ''
                this.$nextTick(() => {
                    if (attempt !== transition || !this.opened) return
                    if (panel.dataset.slot === 'trace-collapsible-content')
                        panel.style.setProperty('--ui-panel-height', `${panel.scrollHeight}px`)
                    getComputedStyle(panel).height
                    requestAnimationFrame(() => {
                        if (attempt !== transition) return
                        delete panel.dataset.startingStyle
                        Promise.allSettled(
                            panel.getAnimations().map((animation) => animation.finished),
                        ).then(() => {
                            if (attempt === transition && this.opened) panel.dataset.settled = ''
                        })
                    })
                })
            })
        },
        get panel() {
            return [
                ...this.$root.querySelectorAll(
                    '[data-slot="collapsible-content"],[data-slot="trace-collapsible-content"]',
                ),
            ].find(
                (panel) =>
                    panel.closest('[data-slot="collapsible"],[data-slot="trace-collapsible"]') ===
                    this.$root,
            )
        },
        open() {
            this.opened = true
        },
        close() {
            this.opened = false
        },
        toggle() {
            this.opened = !this.opened
        },
    }
}

export function carousel(orientation = 'horizontal', loop = false, align = 'center') {
    let engine
    return {
        current: 1,
        pages: 0,
        canPrevious: false,
        canNext: false,
        init() {
            this.$nextTick(() => {
                const viewport = this.$root.querySelector('[data-ui-track]')
                engine = EmblaCarousel(viewport, {
                    axis: orientation === 'horizontal' ? 'x' : 'y',
                    loop,
                    align,
                })
                const update = () => {
                    this.current = engine.selectedScrollSnap() + 1
                    this.pages = engine.scrollSnapList().length
                    this.canPrevious = engine.canScrollPrev()
                    this.canNext = engine.canScrollNext()
                }
                engine.on('select', update).on('reInit', update)
                update()
            })
        },
        goTo(page) {
            engine?.scrollTo(page - 1)
        },
        step(direction) {
            if (direction < 0) engine?.scrollPrev()
            else engine?.scrollNext()
        },
        navigate(event) {
            if (
                event.defaultPrevented ||
                event.target.matches('input,textarea,select') ||
                event.target.isContentEditable
            )
                return
            const previous =
                event.key === 'ArrowLeft' || (orientation === 'vertical' && event.key === 'ArrowUp')
            const next =
                event.key === 'ArrowRight' ||
                (orientation === 'vertical' && event.key === 'ArrowDown')
            if (previous || next) {
                event.preventDefault()
                this.step(previous ? -1 : 1)
            }
        },
        destroy() {
            engine?.destroy()
        },
    }
}

export function resizable(direction = 'horizontal') {
    return {
        direction,
        drag: null,
        init() {
            this.$nextTick(() =>
                this.$root.querySelectorAll('[data-slot="resizable-handle"]').forEach((handle) => {
                    if (handle.closest('[data-slot="resizable"]') !== this.$root) return
                    const first = Number(getComputedStyle(handle.previousElementSibling).flexGrow),
                        second = Number(getComputedStyle(handle.nextElementSibling).flexGrow)
                    handle.setAttribute(
                        'aria-valuenow',
                        String(Math.round((first / (first + second)) * 100)),
                    )
                }),
            )
        },
        start(event) {
            const handle = event.currentTarget
            if (!handle.previousElementSibling || !handle.nextElementSibling) return
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            const axis = this.direction === 'horizontal' ? 'width' : 'height'
            this.drag = {
                handle,
                first,
                second,
                total: first.getBoundingClientRect()[axis] + second.getBoundingClientRect()[axis],
                start: this.direction === 'horizontal' ? event.clientX : event.clientY,
                size: first.getBoundingClientRect()[axis],
            }
            handle.setPointerCapture(event.pointerId)
        },
        move(event) {
            if (!this.drag) return
            const position = this.direction === 'horizontal' ? event.clientX : event.clientY
            this.resize(
                this.drag.handle,
                ((this.drag.size + position - this.drag.start) / this.drag.total) * 100,
            )
        },
        resize(handle, percent) {
            percent = Math.max(10, Math.min(90, percent))
            const first = handle.previousElementSibling,
                second = handle.nextElementSibling
            if (!first || !second) return
            const total =
                Number(getComputedStyle(first).flexGrow) + Number(getComputedStyle(second).flexGrow)
            first.style.setProperty('--ui-panel-size', (total * percent) / 100)
            second.style.setProperty('--ui-panel-size', (total * (100 - percent)) / 100)
            handle.setAttribute('aria-valuenow', String(Math.round(percent)))
        },
        key(event) {
            const keys =
                this.direction === 'horizontal'
                    ? ['ArrowLeft', 'ArrowRight']
                    : ['ArrowUp', 'ArrowDown']
            const value = Number(event.currentTarget.getAttribute('aria-valuenow'))
            if (keys.includes(event.key) || ['Home', 'End'].includes(event.key)) {
                event.preventDefault()
                this.resize(
                    event.currentTarget,
                    event.key === 'Home'
                        ? 10
                        : event.key === 'End'
                          ? 90
                          : value + (event.key === keys[0] ? -5 : 5),
                )
            }
        },
        finish() {
            this.drag = null
        },
        destroy() {
            this.finish()
        },
    }
}

export function registerStep(Alpine) {
    Alpine.directive('ui-step', (element, { expression }, { evaluate, cleanup }) => {
        const button = element.firstElementChild
        if (!button || button.nextElementSibling) throw new Error(element.dataset.uiSlotError)
        const direction = evaluate(expression)
        const initiallyDisabled = button.hasAttribute('disabled')
        const release = Alpine.bind(button, {
            ':disabled'() {
                return (
                    initiallyDisabled ||
                    (element.closest('[data-slot="carousel"]')
                        ? evaluate(direction < 0 ? '!canPrevious' : '!canNext')
                        : false)
                )
            },
            '@click'(event) {
                if (button.matches(':disabled,[aria-disabled="true"]')) return
                event.preventDefault()
                evaluate(`step(${direction})`)
            },
        })
        cleanup(() => release?.())
    })
}
