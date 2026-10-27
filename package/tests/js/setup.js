/*
 * jsdom has neither the Popover API, :modal, matchMedia nor ResizeObserver. Just enough for the tests:
 * open popovers carry data-popover-open, modal dialogs data-modal.
 */

const matches = Element.prototype.matches
const closest = Element.prototype.closest
const querySelector = Document.prototype.querySelector
const querySelectorAll = Element.prototype.querySelectorAll
const documentQuerySelectorAll = Document.prototype.querySelectorAll

const translate = selector => selector.replaceAll(':popover-open', '[data-popover-open]').replaceAll(':modal', '[data-modal]')

Element.prototype.matches = function (selector) { return matches.call(this, translate(selector)) }
Element.prototype.closest = function (selector) { return closest.call(this, translate(selector)) }
Element.prototype.querySelectorAll = function (selector) { return querySelectorAll.call(this, translate(selector)) }
Document.prototype.querySelector = function (selector) { return querySelector.call(this, translate(selector)) }
Document.prototype.querySelectorAll = function (selector) { return documentQuerySelectorAll.call(this, translate(selector)) }

const toggle = (element, open) => {
    element.toggleAttribute('data-popover-open', open)
    element.dispatchEvent(Object.assign(new Event('toggle'), { newState: open ? 'open' : 'closed' }))
}

Element.prototype.scrollIntoView ||= function () {}

HTMLElement.prototype.showPopover = function () { toggle(this, true) }
HTMLElement.prototype.hidePopover = function () { toggle(this, false) }
HTMLElement.prototype.togglePopover = function (force) { toggle(this, force ?? ! this.hasAttribute('data-popover-open')) }

HTMLDialogElement.prototype.showModal = function () {
    this.setAttribute('open', '')
    this.setAttribute('data-modal', '')
}

HTMLDialogElement.prototype.close = function (value) {
    if (value !== undefined) this.returnValue = value
    this.removeAttribute('open')
    this.removeAttribute('data-modal')
    this.dispatchEvent(new Event('close'))
}

// Desktop unless a test swaps it.
window.matchMedia ||= query => ({ media: query, matches: false, addEventListener() {}, removeEventListener() {} })

// jsdom does no layout, so there is nothing to observe.
window.ResizeObserver ||= class { observe() {} unobserve() {} disconnect() {} }
