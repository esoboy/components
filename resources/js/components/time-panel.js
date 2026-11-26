export function timePanel() {
    return {
        syncUnit(opened) {
            if (!opened) this.$wire.activeUnit = null
            else if (this.$wire.activeUnit) this.revealTime()
        },
        tabStop(option) {
            const previous = option.parentElement.querySelector('[tabindex="0"]')
            if (previous) previous.tabIndex = -1
            option.tabIndex = 0
        },
        revealTime(focus = false, unit = this.$wire.activeUnit) {
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
