import { moveGroupFocus } from '../behaviors/keyboard.js'

export function choiceGroup() {
    return {
        navigate(event) {
            const target = moveGroupFocus(
                event,
                '.ui-toggle-group',
                '.ui-toggle-group-item > input',
            )
            if (target?.type === 'radio') target.click()
        },
    }
}
