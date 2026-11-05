// Livewire remains responsible for the action. This adapter only waits for the
// confirmation UI already rendered by Blade.
export function confirmation(provider) {
    let pending = null, confirmed = false, source = null
    return Object.assign(provider, {
        ask(question, label, action, instead, element) {
            if (pending) pending(false)
            for (const [node, text] of [[this.$el.querySelector('[data-slot$="-title"]'), question], [this.$el.querySelector('[value="confirm"]'), label]]) {
                if (!node) continue
                node.dataset.default ??= node.textContent
                node.textContent = text || node.dataset.default
            }
            source = element; confirmed = false
            pending = result => (result ? action() : instead())
            if (this.$el.matches('dialog')) { this.$el.returnValue = ''; this.$el.showModal() }
            else this.$el.showPopover({ source: element })
        },
        confirmClick(event) { if (event.target.closest('[value="confirm"]')) confirmed = true },
        confirmClosed() {
            if (!pending) return
            const done = pending; pending = null
            done(this.$el.matches('dialog') ? this.$el.returnValue === 'confirm' : confirmed)
            source?.focus(); source = null
        },
    })
}

export function register(Livewire, Alpine) {
    Livewire.hook('directive.init', ({ el, directive }) => {
        if (directive.value !== 'confirm' || directive.modifiers.includes('prompt')) return
        const id = 'ui-confirm' + (directive.modifiers.includes('popover') ? '-popover' : '') + (directive.modifiers.includes('destructive') ? '-destructive' : '')
        const [question, label] = directive.expression.replaceAll('\\n', '\n').split('|')
        el.__livewire_confirm = (action, instead) => {
            const dialog = document.getElementById(id)
            if (!dialog) return window.confirm(question || 'Are you sure?') ? action() : instead()
            Alpine.evaluate(dialog, 'ask(question, label, action, instead, element)', { scope: { question, label, action, instead, element: el } })
        }
    })
}
