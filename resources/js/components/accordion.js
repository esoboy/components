import { moveFocus } from '../behaviors/keyboard.js'

export function accordion() {
    return {
        navigate(event) {
            if (!event.target.matches('.ui-accordion-summary')) return
            const nodes = [...this.$root.querySelectorAll('.ui-accordion-summary')].filter(
                (node) => !node.inert && node.closest('.ui-accordion') === this.$root,
            )
            moveFocus(event, nodes)
        },
    }
}
