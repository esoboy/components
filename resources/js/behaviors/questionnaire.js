export function questionnaire() {
    return {
        focusItem(invalid = false) {
            this.$nextTick(() => {
                const item = this.$root.querySelector('fieldset')
                ;(invalid ? item?.querySelector('input:not(:disabled)') : item)?.focus()
            })
        },
        navigate(event) {
            const input = event.target.matches('input:not([type=radio],[type=checkbox]),textarea'),
                action = this.$root.querySelector(
                    '[data-slot="questionnaire-next"] button, [data-slot="questionnaire-submit"] button',
                ),
                previous = this.$root.querySelector('[data-slot="questionnaire-previous"] button')
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault()
                // Commit any edited freeform control before the following action is bundled.
                if (input) event.target.dispatchEvent(new Event('change', { bubbles: true }))
                action?.click()
                return
            }
            if (input && event.target.value !== '') {
                if (event.key === 'Enter') {
                    event.preventDefault()
                    event.target.dispatchEvent(new Event('change', { bubbles: true }))
                    action?.click()
                }
                return
            }
            if (event.altKey || event.ctrlKey || event.metaKey) return
            const choice = [
                ...this.$root.querySelectorAll('input[data-shortcut]:not(:disabled)'),
            ].find((element) => element.dataset.shortcut.toLowerCase() === event.key.toLowerCase())
            if (choice) {
                event.preventDefault()
                choice.focus()
                choice.click()
            } else if (['ArrowUp', 'ArrowDown'].includes(event.key)) {
                const controls = [...this.$root.querySelectorAll('fieldset input:not(:disabled)')],
                    index = controls.indexOf(event.target),
                    next =
                        controls[
                            (index + (event.key === 'ArrowDown' ? 1 : -1) + controls.length) %
                                controls.length
                        ]
                event.preventDefault()
                next?.focus()
                if (next?.type === 'radio') next.click()
            } else if (event.key === 'Enter' && !event.target.closest('button')) {
                event.preventDefault()
                action?.click()
            } else if (
                ['ArrowLeft', 'ArrowRight'].includes(event.key) &&
                !event.target.matches('input,textarea')
            ) {
                event.preventDefault()
                if (event.key === 'ArrowLeft') previous?.click()
                else if (this.$root.querySelector('fieldset')?.dataset.status !== 'unanswered')
                    action?.click()
            }
        },
    }
}
