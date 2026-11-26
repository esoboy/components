import { dialog } from '../behaviors/overlay.js'

export function tour(readValue) {
    const base = dialog(false)
    let owner,
        highlighted = null
    const destroyDialog = base.destroy
    return Object.defineProperties(
        base,
        Object.getOwnPropertyDescriptors({
            get opened() {
                return !!readValue()
            },
            set opened(value) {
                if (value !== this.opened) this.$root.querySelector('[data-panel-field]').click()
            },
            get index() {
                return owner?.index
            },

            init() {
                owner = this.$wire
                this.$watch('index', () => this.$nextTick(() => this.position()))
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
                target.classList.add('ui-tour-highlight')
                const rect = target.getBoundingClientRect()
                const height = this.panel.offsetHeight
                this.panel.setAttribute('data-target-positioned', '')
                Object.assign(this.panel.style, {
                    left: `${Math.max(8, Math.min(rect.left, innerWidth - this.panel.offsetWidth - 8))}px`,
                    top: `${Math.max(8, Math.min(rect.bottom + 8, innerHeight - height - 8))}px`,
                })
            },
            clearHighlight() {
                if (this.panel?.hasAttribute('data-target-positioned')) {
                    this.panel.removeAttribute('data-target-positioned')
                    this.panel.style.removeProperty('left')
                    this.panel.style.removeProperty('top')
                }
                if (highlighted) {
                    highlighted.classList.remove('ui-tour-highlight')
                    highlighted = null
                }
            },
            destroy() {
                this.clearHighlight()
                destroyDialog.call(this)
            },
        }),
    )
}
