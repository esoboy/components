/*
 * <eso-tour>
 *
 * Shows its steps one after the other. The step's target (a CSS selector) carries the
 * anchor --tour-target, which the spotlight and the panel follow; the panel shows the
 * step's content, cloned from its template, a counter and Previous / Next or Finish.
 * Started by a button (commandfor="<name>" command="--start") or Ui::tour('<name>')->start();
 * closed by its close button, Escape, Finish, command="--close" or ->close(). From the
 * element, tour-change reports { step } and tour-close { step, finished }.
 */

const ANCHOR = '--tour-target'

import { listen, disconnected } from './interaction.js'

export default (binding = {}) => ({
    _step: -1,
    _target: null,
    _returnFocus: null,

    requested(detail) { if (detail.name === this.$el.id) detail.open ? this.start(detail.step ?? 0) : this.close() },
    escape(event) {
        if (event.defaultPrevented || !this.isOpen || document.querySelector('[popover]:not([popover="manual"]):popover-open, dialog:modal')) return
        event.preventDefault(); this.close()
    },
    init() {

        // Invoker commands: <button commandfor="<name>" command="--start">.
        listen(this, this.$el, 'command', event => {
            if (event.command === '--start') this.start()
            if (event.command === '--close') this.close()
        })

        listen(this, this.$el, 'click', event => {
            const action = event.target.closest?.('[data-tour-action]')?.dataset.tourAction

            if (action === 'previous') this.go(this._step - 1)
            if (action === 'next') this.go(this._step + 1)
            if (action === 'finish') this.close(true)
            if (action === 'close') this.close()
        })
    },

    destroy() {
        disconnected(this)
        this._point(null)
    },

    get steps() {
        return [...this.$el.querySelectorAll(':scope > template[data-tour-step]')]
    },

    get content() {
        return this.$el.querySelector(':scope > [data-slot="tour-content"]')
    },

    get spotlight() {
        return this.$el.querySelector(':scope > [data-slot="tour-spotlight"]')
    },

    get isOpen() {
        return this.content?.matches(':popover-open') ?? false
    },

    get step() {
        return this._step
    },

    start(step = 0) {
        if (! this.steps.length || ! this.content) return

        if (! this.isOpen) {
            this._returnFocus = document.activeElement
            this.spotlight?.showPopover()
            this.content.showPopover()
        }

        this.go(step)
    },

    go(index) {
        const step = this.steps[index]

        if (! step || ! this.isOpen) return

        const content = this.content
        const body = content.querySelector('[data-slot="tour-body"]')
        const last = index === this.steps.length - 1
        const action = name => content.querySelector(`[data-tour-action="${name}"]`)

        this._step = index
        this._point(this._find(step), { scroll: true })

        body.replaceChildren(step.content.cloneNode(true))
        this._label(body.querySelector('[data-slot="tour-title"]'), 'aria-labelledby', 'title')
        this._label(body.querySelector('[data-slot="tour-description"]'), 'aria-describedby', 'description')

        // Beside the target as the step says; without one, in the middle.
        if (this._target) {
            content.dataset.side = step.dataset.side ?? 'bottom'
            content.dataset.align = step.dataset.align ?? 'center'
        } else {
            delete content.dataset.side
            delete content.dataset.align
        }

        this.spotlight?.toggleAttribute('data-empty', ! this._target)

        const counter = content.querySelector('[data-slot="tour-counter"]')

        if (counter) counter.textContent = (counter.dataset.format ?? ':current / :total').replace(':current', index + 1).replace(':total', this.steps.length)

        action('previous')?.toggleAttribute('hidden', index === 0)
        action('next')?.toggleAttribute('hidden', last)
        action('finish')?.toggleAttribute('hidden', ! last)
        action(last ? 'finish' : 'next')?.focus({ preventScroll: true })

        this.$dispatch('tour-change', { step: index })
    },

    close(finished = false) {
        if (! this.isOpen) return

        const step = this._step

        this._point(null)
        this.content.hidePopover()
        this.spotlight?.hidePopover()
        this._step = -1
        this._returnFocus?.focus?.({ preventScroll: true })
        this._returnFocus = null

        this.$dispatch('tour-close', { step, finished })
    },

    /**
     * Livewire re-rendered the page: find the step's target again and anchor it, as a
     * morph may have replaced it or reset its style.
     */
    refresh() {
        if (this.isOpen) this._point(this._find(this.steps[this._step]))
    },

    _find(step) {
        return step?.dataset.target ? document.querySelector(step.dataset.target) : null
    },

    // The target keeps its own anchor names (a Popover trigger's, say) beside ours.
    _point(target, { scroll = false } = {}) {
        if (this._target && this._target !== target) {
            this._target.style.setProperty('anchor-name', this._names(this._target).join(', ') || null)
            this._target.removeAttribute('data-tour-target')
        }

        this._target = target

        if (! target) return

        target.style.setProperty('anchor-name', [...this._names(target), ANCHOR].join(', '))
        target.setAttribute('data-tour-target', '')

        // At once, so the panel picks its place with the target in view (as Chrome keeps the place that fit last).
        if (scroll) target.scrollIntoView({ block: 'center' })
    },

    _names(element) {
        return (element.style.getPropertyValue('anchor-name') || '').split(',').map(name => name.trim()).filter(name => name && name !== 'none' && name !== ANCHOR)
    },

    _label(part, attribute, suffix) {
        if (! part) return this.content.removeAttribute(attribute)

        part.id = `${this.$el.id}-${suffix}`
        this.content.setAttribute(attribute, part.id)
    },
})
