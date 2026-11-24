export function timePanel() {
    return {
        active: null,
        syncUnit(opened) {
            if (!opened) this.active = null
            else if (this.active) this.revealTime()
        },
        openUnit(unit) {
            this.active = unit
            this.revealTime(true)
        },
        toggleUnit(unit, keyboard) {
            this.active = this.active === unit ? null : unit
            if (this.active && keyboard) this.revealTime(true)
        },
        finishUnit(option, unit) {
            if (!option.matches('button:enabled')) return
            this.active = null
            this.$nextTick(() => this.$refs[unit + 'Trigger'].focus())
        },
        tabStop(option) {
            const previous = option.parentElement.querySelector('[tabindex="0"]')
            if (previous) previous.tabIndex = -1
            option.tabIndex = 0
        },
        revealTime(focus = false) {
            const unit = this.active
            this.$nextTick(() => {
                const panel = this.$refs[unit]
                if (!panel?.getClientRects().length) return
                const option =
                    panel.querySelector('[aria-selected=true]') ?? panel.querySelector('button')
                const viewport = option.closest('.ui-scroll-area-viewport')
                viewport.scrollTop +=
                    option.getBoundingClientRect().top -
                    viewport.getBoundingClientRect().top -
                    (viewport.clientHeight - option.offsetHeight) / 2
                this.tabStop(option)
                option.scrollIntoView({ block: 'nearest', inline: 'nearest' })
                if (focus) option.focus({ preventScroll: true })
            })
        },
    }
}
