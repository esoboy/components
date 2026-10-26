/*
 * esoboy/ui
 *
 * Import once from the app's entry point:
 *   import '../../vendor/esoboy/ui/resources/js/ui.js'
 */

import { createAppearance } from './appearance.js'

const appearance = createAppearance()

window.UI = { appearance }

// Livewire boots Alpine; expose reactive state to templates as $appearance.
document.addEventListener('alpine:init', () => {
    const state = window.Alpine.reactive({ value: appearance.value, resolved: appearance.resolved })

    appearance.subscribe(({ value, resolved }) => Object.assign(state, { value, resolved }))

    window.Alpine.magic('appearance', () => ({
        get value() {
            return state.value
        },
        get resolved() {
            return state.resolved
        },
        set: value => appearance.set(value),
        toggle: () => appearance.toggle(),
    }))
})
