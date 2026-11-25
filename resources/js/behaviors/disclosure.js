import { owned } from './slot.js'

export function disclosure() {
    let owner
    return {
        init() {
            owner = this.$wire
        },
        get opened() {
            return !!owner?.value
        },
        set opened(open) {
            owner.value = open
        },
        get panel() {
            return owned(this.$root, '[data-ui-disclosure-panel]', '[data-ui-disclosure]')
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
