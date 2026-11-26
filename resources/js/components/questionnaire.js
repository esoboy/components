export function questionnaire() {
    return {
        readAnswer(input, type = 'input') {
            const group = input.closest('[data-question]')
            const field = this.$root.querySelector('ui-model-field')
            const text = group.querySelector('input.ui-questionnaire-input')
            const choices = [...group.querySelectorAll('input[type=radio],input[type=checkbox]')]
            if (group.hasAttribute('data-exclusive')) {
                if (input === text && text.value.trim())
                    choices.forEach((choice) => (choice.checked = false))
                else if (input !== text && text) text.value = ''
            }
            const selected = choices
                .filter((choice) => choice.checked)
                .map((choice) => choice.value)
            const answer = JSON.parse(group.dataset.emptyAnswer)
            answer.choices = selected
            answer.text = text?.value ?? ''
            if (selected.length || answer.text.trim()) answer.status = group.dataset.filledStatus
            const value = {
                ...JSON.parse(group.dataset.answers),
                ...field.value,
                [group.dataset.question]: answer,
            }
            if (input === text)
                group
                    .querySelector('[data-answer-model]')
                    .dispatchEvent(new CustomEvent(type, { bubbles: true, detail: value }))
            else field.edit(value)
        },
        restoreText(event, input) {
            const group = input.closest('[data-question]')
            if (
                event.detail.owner === this.$root.getAttribute('wire:id') &&
                group.dataset.question === event.detail.name &&
                input.value === event.detail.proposal
            ) {
                input.value = this.$wire.externalValue?.[group.dataset.question]?.text ?? ''
            }
        },
        focusItem(invalid = false) {
            this.$nextTick(() => {
                const item = this.$root.querySelector('fieldset')
                ;(invalid ? item?.querySelector('input:not(:disabled)') : item)?.focus()
            })
        },
        navigate(event) {
            if (event.isComposing) return
            const input = event.target.matches('input:not([type=radio],[type=checkbox]),textarea'),
                action = this.$root.querySelector(
                    '.ui-questionnaire-next button, .ui-questionnaire-submit button',
                ),
                previous = this.$root.querySelector('.ui-questionnaire-previous button')
            if (event.key === 'Enter' && (input || event.ctrlKey || event.metaKey)) {
                event.preventDefault()
                // Commit any edited freeform control before the following action is bundled.
                if (input) event.target.dispatchEvent(new Event('change', { bubbles: true }))
                action?.click()
                return
            }
            if (input || event.altKey || event.ctrlKey || event.metaKey) return
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
                else action?.click()
            }
        },
    }
}
