import { dialog } from '../behaviors/overlay.js'

export function sidebar(collapsible = 'offcanvas') {
    const modal = dialog(false)
    let media,
        resize,
        owner,
        root,
        changing = false
    return {
        mobileOpen: false,
        get opened() {
            return this.mobile ? this.mobileOpen : !!owner?.value
        },
        set opened(value) {
            if (this.mobile) this.mobileOpen = value
            else owner.value = value
        },
        mobile: false,
        returnFocus: null,
        get tooltipsDisabled() {
            return this.mobile || this.opened
        },
        get panel() {
            return root?.querySelector('.ui-sidebar-content')
        },
        init() {
            root = this.$root
            owner = this.$wire
            media = matchMedia('(max-width: 767px)')
            resize = () => {
                changing = true
                modal.destroy.call(this)
                if (this.panel) this.panel.open = false
                this.mobile = media.matches && collapsible !== 'none'
                this.mobileOpen = false
                this.$nextTick(() => {
                    changing = false
                    this.sync()
                })
            }
            media.addEventListener('change', resize)
            this.$watch('opened', () => {
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
            modal.destroy.call(this)
        },
    }
}
