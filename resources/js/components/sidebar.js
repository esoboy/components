import { dialog } from '../behaviors/overlay.js'

export function sidebar(collapsible, internal, readValue) {
    const modal = dialog(false)
    let resize,
        root,
        changing = false
    return {
        get opened() {
            return this.mobile && internal ? this.mobileOpen : !!readValue()
        },
        set opened(value) {
            if (this.mobile && internal) this.mobileOpen = value
            else if (value !== this.opened) root.querySelector('[data-panel-field]').click()
        },
        mobile: false,
        mobileOpen: false,
        returnFocus: null,
        get tooltipsDisabled() {
            return this.mobile || !!readValue()
        },
        get panel() {
            return root?.querySelector('.ui-sidebar-content')
        },
        init() {
            root = this.$root
            resize = () => {
                const mobile =
                    getComputedStyle(root).getPropertyValue('--ui-sidebar-mobile').trim() === '1' &&
                    collapsible !== 'none'
                if (mobile === this.mobile && this.panel?.open === (mobile ? this.opened : true))
                    return
                changing = true
                modal.destroy.call(this)
                if (this.panel) this.panel.open = false
                this.mobile = mobile
                this.mobileOpen = false
                this.$nextTick(() => {
                    changing = false
                    this.sync()
                })
            }
            window.addEventListener('resize', resize)
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
            window.removeEventListener('resize', resize)
            modal.destroy.call(this)
        },
    }
}
