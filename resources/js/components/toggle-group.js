import { moveFocus } from '../behaviors/keyboard.js'

export function choiceGroup() {
    return {
        navigate(event) {
            if (!event.target.matches('input')) return
            const nodes = [...this.$root.querySelectorAll('input:not(:disabled)')]
            const target = moveFocus(event, nodes, this.$root.dataset.orientation === 'vertical')
            if (target?.type === 'radio') target.click()
        },
    }
}
