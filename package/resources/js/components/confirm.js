/*
 * wire:confirm="Delete this post?" asks through <x-ui::confirm /> when the layout has
 * one. Without it, and for wire:confirm.prompt, Livewire's own confirm() stays.
 */

import { onLivewire } from '../support/livewire.js'

export function register() {
    const ask = (dialog, message) => new Promise(resolve => {
        const title = dialog.querySelector('[data-slot="alert-dialog-title"]')

        title.dataset.default ??= title.textContent
        title.textContent = message || title.dataset.default

        dialog.returnValue = ''
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true })
        dialog.showModal()
    })

    onLivewire(Livewire => {
        // Runs after Livewire's own confirm directive, so this replaces its handler.
        Livewire.hook('directive.init', ({ el, directive }) => {
            if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) return

            const fallback = el.__livewire_confirm
            const message = directive.expression.replaceAll('\\n', '\n')

            el.__livewire_confirm = (action, instead) => {
                const dialog = document.getElementById('ui-confirm')

                if (! dialog) return fallback(action, instead)

                ask(dialog, message).then(confirmed => (confirmed ? action() : instead()))
            }
        })
    })
}
