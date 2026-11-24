export function messageScroller() {
    return {
        anchored: true,
        atStart: true,
        observer: null,
        get viewport() {
            return this.$root.querySelector('.ui-message-scroller-viewport')
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
