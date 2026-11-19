import { dialog, unlockScroll } from './overlay.js'

export function sidebar(initial = true, collapsible = 'offcanvas') {
    const modal = dialog(initial)
    let media,
        resize,
        shortcut,
        desktopOpen = initial,
        changing = false
    return {
        opened: initial,
        mobile: false,
        returnFocus: null,
        get panel() {
            return this.$root.querySelector('[data-slot="sidebar-content"]')
        },
        init() {
            media = matchMedia('(max-width: 767px)')
            resize = () => {
                changing = true
                if (this.panel?.matches(':modal')) this.panel.close()
                else if (this.panel) this.panel.open = false
                unlockScroll(this.panel)
                this.mobile = media.matches && collapsible !== 'none'
                this.opened = this.mobile ? false : desktopOpen
                this.$nextTick(() => {
                    changing = false
                    this.sync()
                })
            }
            shortcut = (event) => {
                if (
                    (event.ctrlKey || event.metaKey) &&
                    event.key === 'b' &&
                    this.$root.contains(document.activeElement)
                ) {
                    event.preventDefault()
                    this.toggle()
                }
            }
            media.addEventListener('change', resize)
            this.$root.addEventListener('keydown', shortcut)
            this.$watch('opened', () => {
                if (!this.mobile) desktopOpen = this.opened
                this.sync()
            })
            this.$nextTick(resize)
        },
        sync() {
            if (!this.panel?.isConnected || changing) return
            if (this.mobile) {
                modal.sync.call(this, this.opened)
            } else {
                this.panel.open = true
            }
        },
        open() {
            this.returnFocus = document.activeElement
            this.opened = true
        },
        close() {
            if (collapsible === 'none' && !this.mobile) return
            this.opened = false
        },
        toggle() {
            this.opened ? this.close() : this.open()
        },
        closed() {
            if (changing || !this.mobile) return
            unlockScroll(this.panel)
            this.opened = false
            this.returnFocus?.focus()
        },
        backdrop(event) {
            if (event.target !== this.panel || !this.mobile) return
            const box = this.panel.getBoundingClientRect()
            if (
                event.clientX < box.left ||
                event.clientX > box.right ||
                event.clientY < box.top ||
                event.clientY > box.bottom
            )
                this.close()
        },
        destroy() {
            media?.removeEventListener('change', resize)
            this.$root.removeEventListener('keydown', shortcut)
            modal.destroy.call(this)
        },
    }
}
