import { control } from '../behaviors/control.js'

export function rating() {
    return {
        ...control(),
        init() {
            this.initControl()
            this.refresh()
        },
        choose(input) {
            const score = Number(input.value)
            this.write(score === Number(this.value) ? null : score, true)
        },
        refresh() {
            for (const radio of this.$root.querySelectorAll('input[type=radio]')) {
                radio.checked = this.value != null && Number(radio.value) === Number(this.value)
            }
        },
    }
}
