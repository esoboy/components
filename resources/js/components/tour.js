import { dialog } from '../behaviors/overlay.js'

export function tour(initial = false) {
    const base = dialog(initial)
    let highlighted = null
    let oldOutline = ''
    const destroyDialog = base.destroy
    return Object.assign(base, {
        index: 0,

        init() {
            this.$watch('opened', (value) => {
                this.sync(value)
                if (value) this.$nextTick(() => this.position())
                else this.clearHighlight()
            })
            this.$nextTick(() => {
                this.sync(this.opened)
                this.position()
            })
        },
        step(direction) {
            const count = this.$root.querySelectorAll('.ui-tour-step').length
            const next = this.index + direction
            if (next >= count) {
                this.close()
                return
            }
            this.index = Math.max(0, next)
            this.$nextTick(() => this.position())
        },
        position() {
            if (!this.opened || !this.panel) return
            this.clearHighlight()
            const step = this.$root.querySelector(`.ui-tour-step[data-index="${this.index}"]`)
            let target
            try {
                target = document.querySelector(step?.dataset.target)
            } catch {
                return
            }
            if (!target) return
            highlighted = target
            oldOutline = target.style.outline
            target.style.outline = '2px solid var(--color-ring)'
            const rect = target.getBoundingClientRect()
            const height = this.panel.offsetHeight
            Object.assign(this.panel.style, {
                margin: '0',
                left: `${Math.max(8, Math.min(rect.left, innerWidth - this.panel.offsetWidth - 8))}px`,
                top: `${Math.max(8, Math.min(rect.bottom + 8, innerHeight - height - 8))}px`,
            })
        },
        clearHighlight() {
            if (highlighted) {
                highlighted.style.outline = oldOutline
                highlighted = null
            }
        },
        destroy() {
            this.clearHighlight()
            destroyDialog.call(this)
        },
    })
}
