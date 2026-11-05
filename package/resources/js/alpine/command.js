// Command owns no application data. Filtering and roving highlight affect only
// the rendered menu; wire:click on each item remains the application's action.
export const matches = (query, text) => {
    let position = 0
    for (const character of query.toLocaleLowerCase().replace(/\s+/g, '')) {
        position = text.indexOf(character, position)
        if (position < 0) return false
        position++
    }
    return true
}

export default () => ({
    get input() { return this.$el.querySelector('[data-command-input]') },
    get items() { return [...this.$el.querySelectorAll('[data-slot="command-item"]')] },
    get available() { return this.items.filter(item => !item.hidden && item.getAttribute('aria-disabled') !== 'true') },
    init() {
        const list = this.$el.querySelector('[data-slot="command-list"]')
        if (list && this.input) { list.id ||= this.$id('command-list'); this.input.setAttribute('aria-controls', list.id) }
        this.refresh()
    },
    refresh() {
        const query = this.input?.value.trim() ?? ''
        const filtering = this.$el.dataset.filter !== 'false' && query !== ''
        for (const item of this.items) {
            item.id ||= this.$id('command-item', this.items.indexOf(item))
            item.hidden = filtering && !matches(query, `${item.dataset.value ?? ''} ${item.textContent} ${item.dataset.keywords ?? ''}`.toLocaleLowerCase())
        }
        for (const group of this.$el.querySelectorAll('[data-slot="command-group"]')) group.hidden = ![...group.querySelectorAll('[data-slot="command-item"]')].some(item => !item.hidden)
        for (const separator of this.$el.querySelectorAll('[data-slot="command-separator"]')) separator.hidden = filtering
        this.$el.toggleAttribute('data-empty', !this.items.some(item => !item.hidden))
        this.highlight(this.available.find(item => item.dataset.selected === 'true') ?? this.available[0], false)
    },
    highlight(target, scroll = true) {
        for (const item of this.items) { item.dataset.selected = String(item === target); item.setAttribute('aria-selected', String(item === target)) }
        if (target) { this.input?.setAttribute('aria-activedescendant', target.id); if (scroll) target.scrollIntoView({ block: 'nearest' }) }
        else this.input?.removeAttribute('aria-activedescendant')
    },
    hover(event) {
        const item = event.target.closest('[data-slot="command-item"]')
        if (this.available.includes(item)) this.highlight(item)
    },
    key(event) {
        const items = this.available
        const index = items.findIndex(item => item.dataset.selected === 'true')
        let next
        if (event.key === 'ArrowDown') next = Math.min(index + 1, items.length - 1)
        else if (event.key === 'ArrowUp') next = Math.max(0, index - 1)
        else if (event.key === 'Home') next = 0
        else if (event.key === 'End') next = items.length - 1
        else if (event.key === 'Enter') { event.preventDefault(); items[index]?.click(); return }
        if (next !== undefined) { event.preventDefault(); this.highlight(items[next]) }
    },
})
