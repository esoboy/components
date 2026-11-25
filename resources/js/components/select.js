import { popover } from '../behaviors/overlay.js'

export function nativeSelect() {
    return Object.assign(popover(), {
        reflectField(field, value) {
            const selected = new Set([value].flat())
            for (const option of field.options) {
                option.selected =
                    option.hasAttribute('data-choice') &&
                    selected.has(JSON.parse(option.dataset.choice))
            }
        },
    })
}
