import { dialog } from './overlay.js'

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
                modal.destroy.call(this)
                if (this.panel) this.panel.open = false
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
        open: modal.open,
        close() {
            if (collapsible === 'none' && !this.mobile) return
            this.opened = false
        },
        toggle: modal.toggle,
        closed() {
            if (changing || !this.mobile) return
            modal.closed.call(this)
        },
        backdrop(event) {
            if (this.mobile) modal.backdrop.call(this, event)
        },
        destroy() {
            media?.removeEventListener('change', resize)
            this.$root.removeEventListener('keydown', shortcut)
            modal.destroy.call(this)
        },
    }
}
