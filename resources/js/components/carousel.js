import EmblaCarousel from 'embla-carousel'

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
