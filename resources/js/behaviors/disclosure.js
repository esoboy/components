export function disclosure(initial = false, transitionHooks) {
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
                    transitionHooks?.close.call(this, panel, () => attempt === transition)
                    return
                }
                panel.style.removeProperty('transition')
                panel.style.removeProperty('height')
                panel.dataset.startingStyle = ''
                this.$nextTick(() => {
                    if (attempt !== transition || !this.opened) return
                    transitionHooks?.open(panel)
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
            return [...this.$root.querySelectorAll('[data-ui-disclosure-panel]')].find(
                (panel) => panel.closest('[data-ui-disclosure]') === this.$root,
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
