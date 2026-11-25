import { popover } from '../behaviors/overlay.js'
import { slotControl } from '../behaviors/slot.js'

export function navigationMenu(openDelay = 150, closeDelay = 150) {
    const base = popover(function () {
        const content = this.contents.find((item) => item.dataset.value === this.active)
        if (content) this.measure(content)
    })
    let timer,
        attempt = 0,
        focus,
        returnFocus = false
    return Object.defineProperties(
        Object.create(Object.getPrototypeOf(base), Object.getOwnPropertyDescriptors(base)),
        Object.getOwnPropertyDescriptors({
            active: null,
            pinned: false,
            keyboardFocus: false,
            preservePositionFraction: true,
            onEscape() {
                this.keyboardFocus = true
            },
            get popup() {
                return this.panel.querySelector('.ui-navigation-menu-popup')
            },
            get contents() {
                return [...this.panel.querySelectorAll('.ui-navigation-menu-content')].filter(
                    (e) => e.closest('.ui-navigation-menu') === this.$root,
                )
            },
            get triggers() {
                return [...this.$root.querySelectorAll('[data-ui-navigation-value]')].filter(
                    (e) => e.closest('.ui-navigation-menu') === this.$root,
                )
            },
            get trigger() {
                return slotControl(
                    this.triggers.find((e) => e.dataset.uiNavigationValue === this.active),
                )
            },
            init() {
                base.init.call(this)
                focus = (event) => {
                    if (this.opened && !this.$root.contains(event.target)) this.close()
                }
                document.addEventListener('focusin', focus)
            },
            schedule(value) {
                clearTimeout(timer)
                const focus = this.keyboardFocus
                if (this.panel.matches(':popover-open')) this.openItem(value, focus, true)
                else timer = setTimeout(() => this.openItem(value, focus, true), openDelay)
            },
            leave() {
                clearTimeout(timer)
                if (!this.opened) returnFocus = false
                if (!this.pinned) timer = setTimeout(() => this.close(), closeDelay)
            },
            stay() {
                clearTimeout(timer)
            },
            measure(content) {
                const position = content.style.position,
                    height = content.style.height
                content.style.position = 'fixed'
                content.style.height = 'auto'
                const size = { width: content.offsetWidth, height: content.offsetHeight }
                content.style.position = position
                content.style.height = height
                this.panel.style.width = `${size.width}px`
                this.panel.style.height = `${size.height}px`
                this.popup.style.width = `${size.width}px`
                this.popup.style.height = `${size.height}px`
                if (this.panel.hasAttribute('data-instant')) {
                    getComputedStyle(this.popup).width
                    this.popup.removeAttribute('data-instant')
                }
                base.position.call(this)
            },
            openItem(value, focus = false, hover = false) {
                clearTimeout(timer)
                const content = this.contents.find((e) => e.dataset.value === value)
                const wrapper = this.triggers.find((e) => e.dataset.uiNavigationValue === value)
                if (!content || slotControl(wrapper)?.matches(':disabled,[aria-disabled="true"]'))
                    return
                if (this.opened && this.active === value) {
                    if (focus) this.focusContent(content, value)
                    return
                }
                const sequence = ++attempt,
                    previous = this.contents.find((e) => e.dataset.value === this.active),
                    switching = this.opened
                this.pinned = !hover
                this.active = value
                for (const item of this.contents) {
                    item.hidden = item !== content && (!switching || item !== previous)
                    item.inert = item !== content
                    item.toggleAttribute('data-leaving', switching && item === previous)
                    item.toggleAttribute('data-active', item === content)
                    item.removeAttribute('data-entering')
                }
                content.dataset.entering = ''
                delete this.panel.dataset.ending
                this.panel.toggleAttribute('data-switching', switching)
                if (!switching) {
                    this.panel.dataset.instant = ''
                    this.popup.setAttribute('data-instant', '')
                    this.popup.style.width = ''
                    this.popup.style.height = ''
                    base.open.call(this, false)
                }
                this.measure(content)
                getComputedStyle(content).opacity
                requestAnimationFrame(() => {
                    if (sequence !== attempt) return
                    delete content.dataset.entering
                    delete this.panel.dataset.instant
                    Promise.allSettled(
                        [this.popup, content].flatMap((e) =>
                            e.getAnimations().map((a) => a.finished),
                        ),
                    ).then(() => {
                        if (sequence !== attempt) return
                        if (previous && previous !== content) {
                            previous.hidden = true
                            delete previous.dataset.leaving
                        }
                        delete this.panel.dataset.switching
                    })
                })
                if (focus) this.focusContent(content, value)
            },
            toggleItem(value, focus = false) {
                if (this.opened && this.active === value) {
                    if (this.pinned) this.close()
                    else this.pinned = true
                } else this.openItem(value, focus || this.keyboardFocus)
            },
            focusContent(content, value) {
                this.$nextTick(() =>
                    requestAnimationFrame(() => {
                        if (this.opened && this.active === value)
                            content
                                .querySelector('a,button,[tabindex="0"]')
                                ?.focus({ preventScroll: true })
                    }),
                )
            },
            key(event) {
                if (this.panel.contains(event.target)) return
                this.keyboardFocus = true
                const controls = [...this.$root.querySelectorAll('button,a[href]')].filter(
                    (e) =>
                        !this.panel.contains(e) &&
                        e.closest('.ui-navigation-menu') === this.$root &&
                        !e.matches(':disabled,[aria-disabled="true"]'),
                )
                const index = controls.indexOf(document.activeElement)
                if (['ArrowLeft', 'ArrowRight'].includes(event.key)) {
                    event.preventDefault()
                    controls[
                        (index + (event.key === 'ArrowRight' ? 1 : -1) + controls.length) %
                            controls.length
                    ]?.focus()
                } else if (event.key === 'ArrowDown') {
                    const value = document.activeElement.closest('[data-ui-navigation-value]')
                        ?.dataset.uiNavigationValue
                    if (value !== undefined) {
                        event.preventDefault()
                        this.openItem(value, true)
                    }
                }
            },
            navigate(event) {
                if (!['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight'].includes(event.key)) return
                const content = event.target.closest('.ui-navigation-menu-content')
                if (
                    !this.contents.includes(content) ||
                    event.target.matches('input,textarea,select')
                )
                    return
                const items = [...content.querySelectorAll('a[href],button')].filter(
                    (e) =>
                        !e.matches(':disabled,[aria-disabled="true"]') && e.getClientRects().length,
                )
                const step = ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : -1
                event.preventDefault()
                event.stopPropagation()
                items[
                    (items.indexOf(document.activeElement) + step + items.length) % items.length
                ]?.focus()
            },
            close(restoreFocus = false) {
                clearTimeout(timer)
                returnFocus = restoreFocus
                if (!this.opened) return
                const sequence = ++attempt
                this.opened = false
                this.panel.dataset.ending = ''
                Promise.allSettled(this.popup.getAnimations().map((a) => a.finished)).then(() => {
                    if (sequence !== attempt || this.opened) return
                    if (this.panel.contains(document.activeElement)) document.activeElement.blur()
                    this.panel.hidePopover()
                    if (returnFocus) this.trigger?.focus({ preventScroll: true })
                    delete this.panel.dataset.ending
                    this.untrack()
                    for (const item of this.contents) {
                        item.hidden = true
                        delete item.dataset.leaving
                    }
                })
            },
            destroy() {
                attempt++
                clearTimeout(timer)
                document.removeEventListener('focusin', focus)
                base.destroy.call(this)
            },
        }),
    )
}
