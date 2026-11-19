export function scrollArea() {
    let observer, viewport, scrollRange, dragOrigin, scrollOrigin
    return {
        overflow: false,
        thumb: 100,
        offset: 0,
        get vertical() {
            return this.$root.dataset.orientation !== 'horizontal'
        },
        get extent() {
            return this.vertical ? 'Height' : 'Width'
        },
        get scrollProperty() {
            return this.vertical ? 'scrollTop' : 'scrollLeft'
        },
        init() {
            viewport = this.$el.firstElementChild
            observer = new ResizeObserver(() => this.measure())
            observer.observe(viewport)
            observer.observe(viewport.firstElementChild)
        },
        measure() {
            const size = viewport['client' + this.extent],
                total = viewport['scroll' + this.extent]
            scrollRange = total - size
            this.overflow = size > 0 && scrollRange > 0
            if (!this.overflow) return
            this.thumb = Math.max(10, (size / total) * 100)
            this.offset =
                (viewport[this.scrollProperty] / scrollRange) * (100 / this.thumb - 1) * 100
        },
        start(event) {
            if (!event.isPrimary || event.button !== 0) return
            event.currentTarget.setPointerCapture(event.pointerId)
            dragOrigin = this.vertical ? event.clientY : event.clientX
            scrollOrigin = viewport[this.scrollProperty]
        },
        move(event) {
            if (!this.overflow || !event.currentTarget.hasPointerCapture(event.pointerId)) return
            const position = this.vertical ? event.clientY : event.clientX
            const track = event.currentTarget['client' + this.extent] - 2
            viewport[this.scrollProperty] =
                scrollOrigin +
                ((position - dragOrigin) / (track * (1 - this.thumb / 100))) * scrollRange
        },
        destroy() {
            observer.disconnect()
        },
    }
}
