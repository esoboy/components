import { owned } from './slot.js'

export function disclosure(readValue) {
    return {
        get opened() {
            return !!readValue()
        },
        get panel() {
            return owned(this.$root, '[data-ui-disclosure-panel]', '[data-ui-disclosure]')
        },
        toggle() {
            this.$root.querySelector('[data-panel-field]').click()
        },
    }
}
