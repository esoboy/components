import { bindSlotControl } from './slot.js'

export function registerStep(Alpine) {
    Alpine.directive('ui-step', (element, { expression }, { evaluate, cleanup }) => {
        const direction = evaluate(expression)
        const disabledWhen = element.dataset.uiStepDisabled
        function bind(button) {
            const initiallyDisabled = button.hasAttribute('disabled')
            return Alpine.bind(button, {
                ':disabled'() {
                    return initiallyDisabled || (disabledWhen ? evaluate(disabledWhen) : false)
                },
                '@click'(event) {
                    if (button.matches(':disabled,[aria-disabled="true"]')) return
                    event.preventDefault()
                    evaluate(`step(${direction})`)
                },
            })
        }
        cleanup(bindSlotControl(element, bind, () => element.dataset.uiSlotError))
    })
}
