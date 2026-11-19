import { enabled, moveFocus } from './navigation.js'

const modals = new Set()
const popovers = new Map()
let previousOverflow = ''

function handlePopoverKey(event) {
    let top, topPanel
    for (const [panel, behavior] of popovers) {
        behavior.pointerFocus = false
        delete panel.dataset.pointerFocus
        top = behavior
        topPanel = panel
    }
    if (!top || event.key !== 'Escape' || event.defaultPrevented) return
    event.preventDefault()
    event.stopImmediatePropagation()
    if (topPanel.dataset.slot === 'navigation-menu-positioner') top.keyboardFocus = true
    top.close(topPanel.contains(document.activeElement))
}

export function lockScroll(panel) {
    if (!modals.size) {
        previousOverflow = document.documentElement.style.overflow
        document.documentElement.style.overflow = 'hidden'
    }
    modals.add(panel)
}
export function unlockScroll(panel) {
    if (!modals.delete(panel) || modals.size) return
    document.documentElement.style.overflow = previousOverflow
}
function owned(root, selector) {
    return [...root.querySelectorAll(selector)].find(
        (element) => element.closest('[data-ui-overlay]') === root,
    )
}

function afterExit(panel, callback) {
    panel.dataset.ending = ''
    Promise.allSettled(panel.getAnimations().map((animation) => animation.finished)).then(callback)
}

function focusInPlace(element) {
    element?.focus({ preventScroll: true })
}

export function slotControl(element) {
    let control = element?.firstElementChild
    if (!control || control.nextElementSibling) return null
    while (control?.matches('[data-ui-trigger],[data-slot="tooltip"]')) {
        if (control.dataset.slot === 'tooltip') control = control.querySelector('[data-ui-trigger]')
        control = control?.firstElementChild
    }
    return control
}

export function dialog(initial = false, modal = true) {
    let closing = 0
    return {
        opened: initial,
        returnFocus: null,
        get panel() {
            return owned(this.$root, '[data-ui-panel]')
        },
        init() {
            this.$watch('opened', (value) => this.sync(value))
            this.$nextTick(() => this.sync(this.opened))
        },
        sync(value) {
            if (!this.panel?.isConnected) return
            if (value) {
                closing++
                delete this.panel.dataset.ending
            }
            if (value && !this.panel.open) {
                modal ? this.panel.showModal() : this.panel.show()
                if (modal) lockScroll(this.panel)
            }
            if (!value && this.panel.open) {
                const attempt = ++closing
                afterExit(this.panel, () => {
                    if (attempt !== closing || this.opened || !this.panel?.isConnected) return
                    this.panel.close()
                    delete this.panel.dataset.ending
                    unlockScroll(this.panel)
                })
            }
        },
        open() {
            this.returnFocus = document.activeElement
            this.opened = true
        },
        close() {
            this.opened = false
        },
        toggle() {
            this.opened ? this.close() : this.open()
        },
        closed() {
            unlockScroll(this.panel)
            this.opened = false
            this.returnFocus?.focus()
        },
        backdrop(event) {
            if (event.target !== this.panel || this.panel.role === 'alertdialog') return
            const box = this.panel.getBoundingClientRect()
            if (
                event.clientX < box.left ||
                event.clientX > box.right ||
                event.clientY < box.top ||
                event.clientY > box.bottom
            )
                this.close()
        },
        destroy() {
            closing++
            unlockScroll(this.panel)
            if (this.panel?.open) this.panel.close()
        },
    }
}

function alignedPosition(start, end, size, align, offset, limit) {
    if (align === 'center') return (start + end - size) / 2 + offset
    const atStart = start + offset
    const atEnd = end - size - offset
    const preferred = align === 'end' ? atEnd : atStart
    const alternate = align === 'end' ? atStart : atEnd
    const overflow = (value) => Math.max(0, 5 - value) + Math.max(0, value + size - limit + 5)
    return overflow(alternate) < overflow(preferred) ? alternate : preferred
}

function clamp(value, min, max) {
    return Math.max(min, Math.min(value, max))
}

export function popover() {
    let overlay,
        observer,
        visibilityObserver,
        reposition,
        frame,
        referenceElement,
        referenceBounds,
        pointerOffset,
        alignmentScroll,
        morphListeners = [],
        hoverTimer,
        trackedPanel,
        closing = 0
    return {
        opened: false,
        pointingToOption: false,
        pointerFocus: false,
        get panel() {
            return owned(overlay, '[data-ui-panel]')
        },
        get trigger() {
            return slotControl(owned(overlay, '[data-ui-trigger]'))
        },
        init() {
            overlay = this.$el
            reposition = () => this.position()
        },
        track() {
            if (observer) return
            trackedPanel = this.panel
            if (!popovers.size) document.addEventListener('keydown', handlePopoverKey, true)
            popovers.set(trackedPanel, this)
            observer = new ResizeObserver(reposition)
            observer.observe(trackedPanel)
            visibilityObserver = new IntersectionObserver((entries) => {
                if (
                    entries.some(
                        (entry) => entry.target === referenceElement && !entry.isIntersecting,
                    )
                )
                    this.close()
            })
            if (referenceElement) visibilityObserver.observe(referenceElement)
            // Scrolling or transforming an ancestor can move the trigger without resizing it.
            const follow = () => {
                const rect = referenceElement?.getBoundingClientRect()
                if (
                    rect &&
                    ['x', 'y', 'width', 'height'].some((key) => rect[key] !== referenceBounds[key])
                )
                    reposition()
                frame = requestAnimationFrame(follow)
            }
            frame = requestAnimationFrame(follow)
            morphListeners = ['morphed', 'island.morphed'].map((event) =>
                window.Livewire?.hook(event, reposition),
            )
            window.addEventListener('resize', reposition)
        },
        untrack() {
            if (popovers.delete(trackedPanel) && !popovers.size)
                document.removeEventListener('keydown', handlePopoverKey, true)
            trackedPanel = null
            observer?.disconnect()
            observer = null
            visibilityObserver?.disconnect()
            visibilityObserver = null
            cancelAnimationFrame(frame)
            morphListeners.forEach((release) => release?.())
            morphListeners = []
            window.removeEventListener('resize', reposition)
        },
        toggled(event) {
            if (event.target !== this.panel) return
            // Native toggle is queued; an open notification can arrive after close().
            if (event.newState === 'open' && this.panel.hasAttribute('data-ending')) return
            this.opened = event.newState === 'open'
            this.opened ? this.track() : this.untrack()
        },
        hover(value, delay) {
            clearTimeout(hoverTimer)
            hoverTimer = setTimeout(() => (value ? this.open(false) : this.close()), delay)
        },
        open(focus = true, point = null, focusLast = false) {
            clearTimeout(hoverTimer)
            const panel = this.panel
            const trigger = this.trigger
            if (
                !panel ||
                overlay.dataset.disabled === 'true' ||
                trigger?.matches(':disabled,[aria-disabled="true"]')
            )
                return
            const wasOpen = this.opened
            if (!wasOpen) alignmentScroll = null
            pointerOffset = point
            closing++
            delete panel.dataset.ending
            panel.toggleAttribute('data-pointer-focus', !focus)
            this.pointerFocus = !focus
            panel.showPopover({ source: trigger })
            this.opened = true
            this.position()
            this.track()
            if (panel.dataset.slot === 'combobox-content') {
                if (!wasOpen)
                    this.highlightOption(
                        enabled(panel, '[role="option"][aria-selected="true"]').at(-1),
                    )
                if (focus) focusInPlace(overlay.querySelector('input[role="combobox"]'))
                return
            }
            const role = panel.role
            let activeItem =
                role === 'listbox'
                    ? panel.querySelector('[aria-selected="true"]:not(:disabled)')
                    : null
            if (!focus && role === 'listbox')
                requestAnimationFrame(() => {
                    if (!this.opened) return
                    focusInPlace(activeItem ?? panel)
                })
            if (!focus && role === 'menu') {
                panel.tabIndex = -1
                focusInPlace(panel)
            }
            if (focus && role !== 'tooltip') {
                const target =
                    activeItem ??
                    (focusLast
                        ? enabled(panel, '[role="option"],[role^="menuitem"]').at(-1)
                        : null) ??
                    panel.querySelector(
                        'input:enabled,button:enabled,a[href],[tabindex="0"],[tabindex][aria-selected=true]',
                    ) ??
                    panel
                if (target === panel && !panel.hasAttribute('tabindex')) panel.tabIndex = -1
                focusInPlace(target)
                if (role === 'menu' || role === 'listbox') activeItem = target
            }
            activeItem?.scrollIntoView({ block: 'nearest' })
        },
        close(restoreFocus = false) {
            clearTimeout(hoverTimer)
            const panel = this.panel
            this.opened = false
            if (!panel?.matches(':popover-open')) return
            if (restoreFocus) focusInPlace(this.trigger)
            if (panel.hasAttribute('data-ending')) return
            const attempt = ++closing
            afterExit(panel, () => {
                if (attempt !== closing || this.opened || !panel.isConnected) return
                panel.hidePopover()
                delete panel.dataset.ending
                this.untrack()
            })
        },
        toggle(focus = true) {
            this.opened ? this.close() : this.open(focus)
        },
        select() {
            let root = overlay,
                lastTrigger = this.trigger
            while (root) {
                const panel = owned(root, '[data-ui-panel]')
                if (panel?.role !== 'menu') break
                if (panel.matches(':popover-open')) panel.hidePopover()
                lastTrigger = owned(root, '[data-ui-trigger]')?.firstElementChild ?? lastTrigger
                root = root.parentElement?.closest('[data-ui-overlay]')
            }
            focusInPlace(lastTrigger)
        },
        showAt(event) {
            const bounds = this.trigger.getBoundingClientRect()
            this.open(false, { x: event.clientX - bounds.left, y: event.clientY - bounds.top })
        },
        position() {
            const floating = this.panel
            if (!floating?.matches(':popover-open')) return
            const trigger = this.trigger
            const anchorBox = trigger?.closest('[data-ui-anchor-box]')
            const reference = anchorBox && overlay.contains(anchorBox) ? anchorBox : trigger
            if (!reference) return
            if (reference !== referenceElement) {
                visibilityObserver?.disconnect()
                visibilityObserver?.observe(reference)
                referenceElement = reference
            }
            referenceBounds = referenceElement.getBoundingClientRect()
            const anchor = pointerOffset
                ? new DOMRect(
                      referenceBounds.x + pointerOffset.x,
                      referenceBounds.y + pointerOffset.y,
                      0,
                      0,
                  )
                : referenceBounds
            const { dataset, style } = floating
            const setPixels = (property, value) => style.setProperty(property, `${value}px`)
            const preferredSide = (dataset.preferredSide ??= dataset.side ?? 'bottom')
            const rtl = getComputedStyle(overlay).direction === 'rtl'
            const side =
                preferredSide === 'inline-start'
                    ? rtl
                        ? 'right'
                        : 'left'
                    : preferredSide === 'inline-end'
                      ? rtl
                          ? 'left'
                          : 'right'
                      : preferredSide
            let placedSide = side
            const align = dataset.align ?? 'start'
            const alignOffset = Number(dataset.alignOffset ?? 0)
            const sideOffset = Number(dataset.sideOffset ?? 4)
            const alignedItem =
                dataset.alignTrigger === 'true' &&
                !trigger.closest('[data-touch]') &&
                anchor.top >= 20 &&
                anchor.bottom <= innerHeight - 20 &&
                (floating.querySelector('[role="option"][aria-selected="true"]') ??
                    floating.querySelector('[role="option"]:not(:disabled)'))
            const ratio = devicePixelRatio || 1
            const anchorWidth =
                (Math.round(anchor.right * ratio) - Math.round(anchor.left * ratio)) / ratio
            setPixels('--anchor-width', anchorWidth)
            const choicePanel = floating.hasAttribute('data-match-trigger')
            const boundedPanel = choicePanel || floating.classList.contains('ui-list-popup')
            const space = {
                left: anchor.left - sideOffset - 5,
                right: innerWidth - anchor.right - sideOffset - 5,
                top: anchor.top - sideOffset - 5,
                bottom: innerHeight - anchor.bottom - sideOffset - 5,
            }
            const horizontal = side === 'left' || side === 'right'
            // Measuring at the full window height would reset a list's scroll position.
            if (boundedPanel && !alignedItem)
                setPixels(
                    '--available-height',
                    horizontal ? innerHeight - 10 : Math.max(0, space.top, space.bottom),
                )
            style.minWidth = choicePanel
                ? `${Math.max(dataset.slot === 'combobox-content' ? 0 : 144, Math.min(anchorWidth, innerWidth - 10))}px`
                : ''
            const computed = getComputedStyle(floating)
            const panel = { width: parseFloat(computed.width), height: parseFloat(computed.height) }
            const opposite = { left: 'right', right: 'left', top: 'bottom', bottom: 'top' }[side]
            if (
                horizontal &&
                ['popover-content', 'hover-card-content', 'tooltip-content'].includes(
                    dataset.slot,
                ) &&
                panel.width > Math.max(space.left, space.right)
            ) {
                placedSide = space.bottom >= panel.height ? 'bottom' : 'top'
            } else if (
                space[side] < (horizontal ? panel.width : panel.height) &&
                space[opposite] > space[side]
            ) {
                placedSide = opposite
            }
            let left, top
            if (placedSide === 'left' || placedSide === 'right') {
                left =
                    placedSide === 'left'
                        ? anchor.left - panel.width - sideOffset
                        : anchor.right + sideOffset
                top = alignedPosition(
                    anchor.top,
                    anchor.bottom,
                    panel.height,
                    align,
                    alignOffset,
                    innerHeight,
                )
            } else {
                if (boundedPanel && !alignedItem) {
                    setPixels('--available-height', Math.max(0, space[placedSide]))
                    panel.height = parseFloat(computed.height)
                }
                top =
                    placedSide === 'top'
                        ? anchor.top - panel.height - sideOffset
                        : anchor.bottom + sideOffset
                left = alignedPosition(
                    anchor.left,
                    anchor.right,
                    panel.width,
                    align,
                    alignOffset,
                    innerWidth,
                )
            }
            if (alignedItem) {
                const viewport = alignedItem.closest('[data-slot="scroll-area-viewport"]')
                const popupBox = floating.getBoundingClientRect()
                const row = alignedItem.getBoundingClientRect()
                const center = anchor.top + anchor.height / 2
                const rowCenter = row.top - popupBox.top + viewport.scrollTop + row.height / 2
                const above = Math.min(rowCenter, center - 5)
                const below = Math.min(viewport.scrollHeight - rowCenter, innerHeight - center - 5)
                setPixels('--available-height', above + below)
                panel.height = parseFloat(computed.height)
                const before = Math.min(above, Math.max(panel.height / 2, panel.height - below))
                top = center - before
                const scroll = Math.round(rowCenter - before)
                // Keep the opening alignment without undoing subsequent wheel or keyboard scrolling.
                if (scroll !== alignmentScroll) viewport.scrollTop = scroll
                alignmentScroll = scroll
                const value = trigger.querySelector('[data-slot="select-value"]')
                const text = alignedItem.querySelector('[data-slot="option-text"]')
                if (value && text) {
                    const edge = rtl ? 'right' : 'left'
                    left =
                        popupBox.left +
                        value.getBoundingClientRect()[edge] -
                        text.getBoundingClientRect()[edge]
                }
                placedSide = 'bottom'
            }
            dataset.side = placedSide
            const clampedLeft =
                !pointerOffset && ['left', 'right'].includes(placedSide)
                    ? left
                    : clamp(left, 5, innerWidth - panel.width - 5)
            const clampedTop = clamp(top, 5, innerHeight - panel.height - 5)
            const preserveFraction = alignedItem || dataset.slot === 'navigation-menu-positioner'
            Object.assign(style, {
                left: `${preserveFraction ? clampedLeft : Math.round(clampedLeft * ratio) / ratio}px`,
                top: `${preserveFraction ? clampedTop : Math.round(clampedTop * ratio) / ratio}px`,
            })
            const arrow = floating.querySelector('[data-slot="tooltip-arrow"]')
            const arrowPadding = 5 + (arrow?.offsetWidth ?? 0) / 2
            const arrowWidth = arrow ? floating.clientWidth : panel.width
            const arrowHeight = arrow ? floating.clientHeight : panel.height
            const arrowX = clamp(
                anchor.left + anchor.width / 2 - clampedLeft + (arrowWidth - panel.width) / 2,
                arrowPadding,
                arrowWidth - arrowPadding,
            )
            const arrowY = clamp(
                anchor.top + anchor.height / 2 - clampedTop + (arrowHeight - panel.height) / 2,
                arrowPadding,
                arrowHeight - arrowPadding,
            )
            setPixels(
                '--ui-origin-x',
                placedSide === 'left'
                    ? panel.width + sideOffset
                    : placedSide === 'right'
                      ? -sideOffset
                      : arrowX,
            )
            setPixels(
                '--ui-origin-y',
                pointerOffset
                    ? 0
                    : placedSide === 'top'
                      ? panel.height + sideOffset
                      : placedSide === 'bottom'
                        ? -sideOffset
                        : arrowY,
            )
            setPixels('--ui-arrow-x', arrowX)
            setPixels('--ui-arrow-y', arrowY)
        },
        highlightOption(option, pointer = false) {
            if (!this.panel || option?.matches(':disabled,[aria-disabled="true"]')) return
            this.pointingToOption = pointer
            if (!pointer) option?.scrollIntoView({ block: 'nearest' })
            this.panel
                .querySelectorAll('[role="option"]')
                .forEach((item) => item.toggleAttribute('data-highlighted', item === option))
            const input = overlay.querySelector('input[role="combobox"]')
            if (option?.id) input?.setAttribute('aria-activedescendant', option.id)
            else input?.removeAttribute('aria-activedescendant')
        },
        navigateChips(event) {
            const input = this.$root.querySelector('.ui-combobox-multiple input[role="combobox"]')
            if (!input || input.disabled || input.readOnly) return false
            const chips = [...this.$root.querySelectorAll('[data-slot="combobox-chip"]')]
            const current = chips.indexOf(event.target.closest('[data-slot="combobox-chip"]'))
            const previous =
                getComputedStyle(input).direction === 'rtl' ? 'ArrowRight' : 'ArrowLeft'
            const next = previous === 'ArrowLeft' ? 'ArrowRight' : 'ArrowLeft'
            if (
                current === -1 &&
                event.key === 'Backspace' &&
                event.target === input &&
                input.value === '' &&
                chips.length
            ) {
                event.preventDefault()
                this.highlightOption(null)
                this.$wire.removeLast()
                return true
            }
            let destination
            if (current === -1) {
                if (
                    event.key !== previous ||
                    event.target !== input ||
                    input.selectionStart !== 0 ||
                    !chips.length
                )
                    return false
                destination = chips.length - 1
            } else if (['Backspace', 'Delete'].includes(event.key)) {
                event.preventDefault()
                event.stopPropagation()
                this.highlightOption(null)
                const index = Math.min(current, chips.length - 2)
                this.$wire.choose(JSON.parse(chips[current].dataset.value)).then(() => {
                    const remaining = this.$root.querySelectorAll('[data-slot="combobox-chip"]')
                    focusInPlace(remaining[index] ?? input)
                })
                return true
            } else if (event.key === previous) destination = current - 1
            else if (event.key === next) destination = current + 1
            else if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
                focusInPlace(input)
                this.open(false)
                destination = -1
            } else if (['Enter', ' '].includes(event.key) && event.target === chips[current])
                destination = -1
            else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                focusInPlace(input)
                return false
            } else return false
            event.preventDefault()
            event.stopPropagation()
            focusInPlace(chips[destination] ?? input)
            return true
        },
        navigate(event) {
            if (event.defaultPrevented) return
            const panel = this.panel
            if (panel?.dataset.slot === 'combobox-content') {
                if (this.navigateChips(event)) return
                if (event.key === 'Tab') {
                    this.close()
                    return
                }
                if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return
                const wasOpen = this.opened
                if (!wasOpen && event.key === 'Enter') return
                if (!wasOpen) this.open(false)
                const options = enabled(panel, '[role="option"]')
                const current = options.findIndex((item) => item.hasAttribute('data-highlighted'))
                if (event.key === 'Enter' && current === -1) {
                    this.close()
                    return
                }
                event.preventDefault()
                event.stopPropagation()
                if (event.key === 'Enter') {
                    options[current].click()
                    this.close(true)
                    return
                }
                if (!wasOpen && current !== -1) return
                const index =
                    event.key === 'ArrowDown'
                        ? (current + 1) % options.length
                        : current === -1
                          ? options.length - 1
                          : (current - 1 + options.length) % options.length
                this.highlightOption(options[index] ?? null)
                return
            }
            if (
                event.target.matches('input:not([type=checkbox],[type=radio]),textarea') &&
                !['ArrowDown', 'ArrowUp', 'Escape'].includes(event.key)
            )
                return
            const items = enabled(panel, '[role="option"],[role^="menuitem"],button').filter(
                (item) => item.closest('[data-ui-panel]') === panel,
            )
            if (moveFocus(event, items)) event.stopPropagation()
            if (event.key === 'ArrowLeft' && this.$root.hasAttribute('data-ui-submenu')) {
                event.preventDefault()
                event.stopPropagation()
                this.close(true)
            }
            if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && event.key !== ' ') {
                const index = items.indexOf(document.activeElement)
                const rotated = [...items.slice(index + 1), ...items.slice(0, index + 1)]
                rotated
                    .find((item) =>
                        (item.textContent || item.parentElement.textContent)
                            .trim()
                            .toLowerCase()
                            .startsWith(event.key.toLowerCase()),
                    )
                    ?.focus()
            }
            if (event.key === 'Tab') this.close()
        },
        destroy() {
            closing++
            clearTimeout(hoverTimer)
            if (this.panel?.matches(':popover-open')) this.panel.hidePopover()
            this.untrack()
        },
    }
}

export function registerTrigger(Alpine) {
    Alpine.directive('ui-trigger', (element, { expression }, { evaluate, cleanup }) => {
        const control = slotControl(element)
        if (!control) throw new Error(JSON.parse(element.dataset.uiErrors).control)
        const operation = evaluate(expression)
        const panelRole = () => evaluate('panel?.role')
        const bind = (attributes) => cleanup(Alpine.bind(control, attributes))
        if (operation === 'navigation') {
            const value = element.dataset.uiNavigationValue
            const scope = { navigationValue: value }
            bind({
                ':aria-controls': () => evaluate('opened ? panel?.id : null'),
                ':popovertarget': () => evaluate('opened ? panel?.id : null'),
                ':aria-expanded': () =>
                    String(evaluate('opened && active === navigationValue', { scope })),
                ':data-panel-open': () =>
                    evaluate('opened && active === navigationValue', { scope }) ? '' : null,
                '@pointerenter': (event) => {
                    if (event.pointerType !== 'touch')
                        evaluate('schedule(navigationValue)', { scope })
                },
                '@mousemove': () => evaluate('keyboardFocus = false'),
                '@click': (event) => {
                    event.preventDefault()
                    evaluate('toggleItem(navigationValue, navigationKeyboard)', {
                        scope: { ...scope, navigationKeyboard: event.detail === 0 },
                    })
                },
            })
            return
        }
        if (panelRole() === 'tooltip') {
            const describedby = control.getAttribute('aria-describedby')
            bind({
                ':aria-describedby': () =>
                    [describedby, evaluate('panel?.id')].filter(Boolean).join(' '),
            })
            return
        }
        let pointerHandled = false
        if (!['open', 'close', 'toggle', 'contextmenu'].includes(operation))
            throw new Error(JSON.parse(element.dataset.uiErrors).operation)
        const bindings = {
            ':popovertarget'() {
                return panelRole() === 'listbox' ? evaluate('panel?.id') : null
            },
            '@pointerdown'(event) {
                pointerHandled = false
                if (
                    operation !== 'toggle' ||
                    event.button !== 0 ||
                    event.pointerType !== 'mouse' ||
                    control.matches(':disabled,[aria-disabled="true"]') ||
                    panelRole() !== 'listbox'
                )
                    return
                pointerHandled = true
                evaluate('toggle(false)')
            },
            '@click'(event) {
                if (pointerHandled && event.detail > 0) {
                    pointerHandled = false
                    event.preventDefault()
                    return
                }
                pointerHandled = false
                if (control.matches(':disabled,[aria-disabled="true"]')) return
                event.preventDefault()
                const pointerMenu = event.detail > 0 && ['menu', 'listbox'].includes(panelRole())
                evaluate(
                    `${operation}(${operation === 'close' ? 'true' : pointerMenu ? 'false' : ''})`,
                )
            },
            ':aria-controls'() {
                evaluate('opened')
                return operation === 'close' ? null : evaluate('panel?.id')
            },
            ':aria-haspopup'() {
                const role = panelRole()
                return operation === 'close'
                    ? null
                    : ['menu', 'listbox', 'dialog', 'alertdialog'].includes(role)
                      ? role
                      : control.getAttribute('aria-haspopup')
            },
            ':aria-expanded'() {
                return operation === 'close' || evaluate('$root.dataset.slot') === 'sidebar'
                    ? null
                    : String(evaluate('opened'))
            },
            ':data-panel-open'() {
                return evaluate('opened') ? '' : null
            },
            '@keydown'(event) {
                if (
                    operation !== 'close' &&
                    element.closest('[data-ui-submenu]') &&
                    event.key === 'ArrowRight'
                ) {
                    event.preventDefault()
                    event.stopPropagation()
                    evaluate('open()')
                    return
                }
                if (
                    operation === 'close' ||
                    !['ArrowDown', 'ArrowUp'].includes(event.key) ||
                    control.matches(':disabled,[aria-disabled="true"]')
                )
                    return
                event.preventDefault()
                evaluate(event.key === 'ArrowUp' ? 'open(true, null, true)' : 'open()')
            },
        }
        if (operation === 'contextmenu') {
            delete bindings['@pointerdown']
            delete bindings['@click']
            delete bindings['@keydown']
        }
        bind(bindings)
        queueMicrotask(() => {
            const panel = evaluate('panel')
            if (operation !== 'close' && panel?.id) control.setAttribute('aria-controls', panel.id)
        })
    })
}
