import { clamp } from './clamp.js'
import { enabled, navigatePopupList } from './keyboard.js'
import { bindSlotControl, observeChildren, slotControl } from './slot.js'

const modals = new Set()
const popovers = new Map()
let previousOverflow = ''

function handlePopoverKey(event) {
    let top, topPanel
    for (const [panel, behavior] of popovers) {
        if (!panel.matches(':popover-open')) continue
        behavior.pointerFocus = false
        delete panel.dataset.pointerFocus
        top = behavior
        topPanel = panel
    }
    if (!top || event.key !== 'Escape' || event.defaultPrevented) return
    event.preventDefault()
    event.stopImmediatePropagation()
    top.onEscape?.()
    top.close(topPanel.contains(document.activeElement))
}

function lockScroll(panel) {
    if (!modals.size) {
        previousOverflow = document.documentElement.style.overflow
        document.documentElement.style.overflow = 'hidden'
    }
    modals.add(panel)
}
function unlockScroll(panel) {
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

export function focusInPlace(element) {
    element?.focus({ preventScroll: true })
}

export function dialog(initial = false, modal = true) {
    let closing = 0
    let activePanel, stopObserving, stopMorphing
    function releasePanel() {
        stopObserving?.()
        stopMorphing?.()
        stopObserving = stopMorphing = null
        unlockScroll(activePanel)
        activePanel = null
    }
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
            const panel = this.panel
            if (!panel?.isConnected) return
            if (value) {
                closing++
                delete panel.dataset.ending
            }
            if (value && !panel.open) {
                modal ? panel.showModal() : panel.show()
                activePanel = panel
                if (modal) lockScroll(panel)
                // The browser owns the open attribute while Blade updates the rest of the panel.
                stopMorphing = window.Livewire?.hook('morph.updating', ({ el, toEl }) => {
                    if (el === panel && panel.open) toEl.setAttribute('open', '')
                })
                stopObserving = observeChildren(this.$root, () => {
                    if (panel.isConnected) return
                    releasePanel()
                    if (this.panel && this.opened) this.sync(true)
                    else this.closed(panel)
                })
            }
            if (!value && panel.open) {
                const attempt = ++closing
                afterExit(panel, () => {
                    if (attempt !== closing || this.opened || !panel.isConnected) return
                    panel.close()
                    delete panel.dataset.ending
                    releasePanel()
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
            if (this.panel?.open) return
            releasePanel()
            this.opened = false
            if (this.returnFocus?.isConnected) {
                focusInPlace(this.returnFocus)
                if (document.activeElement === this.returnFocus) return
            }
            focusInPlace(slotControl(owned(this.$root, '[data-ui-trigger]')))
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
            if (activePanel?.open) activePanel.close()
            releasePanel()
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

export function popover(onViewportResize) {
    let overlay,
        observer,
        visibilityObserver,
        stopObserving,
        reposition,
        viewportResize,
        frame,
        referenceElement,
        referenceBounds,
        pointerOffset,
        alignedViewport,
        alignmentScroll,
        morphListeners = [],
        hoverTimer,
        trackedPanel,
        closing = 0
    return {
        opened: false,
        navigate: navigatePopupList,
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
            viewportResize = onViewportResize?.bind(this) ?? reposition
        },
        track() {
            const panel = this.panel
            if (!panel || trackedPanel === panel) return
            if (trackedPanel) this.untrack()
            trackedPanel = panel
            if (!popovers.size) document.addEventListener('keydown', handlePopoverKey, true)
            popovers.set(trackedPanel, this)
            // Livewire can remove the panel before its queued native toggle event is handled.
            stopObserving = observeChildren(overlay, () => {
                if (panel.closest('[data-ui-overlay]') === overlay) return
                this.untrack()
                this.opened = !!this.panel?.matches(':popover-open')
                if (this.opened) this.track()
            })
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
            window.addEventListener('resize', viewportResize)
        },
        untrack() {
            stopObserving?.()
            stopObserving = null
            if (popovers.delete(trackedPanel) && !popovers.size)
                document.removeEventListener('keydown', handlePopoverKey, true)
            trackedPanel = null
            alignedViewport = null
            observer?.disconnect()
            observer = null
            visibilityObserver?.disconnect()
            visibilityObserver = null
            cancelAnimationFrame(frame)
            morphListeners.forEach((release) => release?.())
            morphListeners = []
            window.removeEventListener('resize', viewportResize)
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
            this.focusOnOpen(panel, focus, focusLast, wasOpen)
        },
        focusOnOpen(panel, focus, focusLast) {
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
                        'input:enabled:not([type=hidden]),select:enabled,textarea:enabled,button:enabled,a[href],[tabindex="0"],[tabindex][aria-selected=true]',
                    ) ??
                    panel
                if (target === panel && !panel.hasAttribute('tabindex')) panel.tabIndex = -1
                focusInPlace(target)
                if (role === 'menu' || role === 'listbox') activeItem = target
            }
            activeItem?.scrollIntoView({ block: 'nearest' })
        },
        close(restoreFocus = false, animate = true) {
            clearTimeout(hoverTimer)
            const panel = this.panel
            this.opened = false
            if (!panel?.matches(':popover-open')) return
            if (restoreFocus) focusInPlace(this.trigger)
            if (panel.hasAttribute('data-ending')) return
            const attempt = ++closing
            const finish = () => {
                if (attempt !== closing || this.opened || !panel.isConnected) return
                panel.hidePopover()
                delete panel.dataset.ending
                this.untrack()
            }
            if (animate) afterExit(panel, finish)
            else finish()
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
        selectedOption(panel) {
            return panel.querySelector('[role="option"][aria-selected="true"]')
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
                (this.selectedOption(floating) ??
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
                ? `${Math.max(Number(dataset.matchTrigger || 144), Math.min(anchorWidth, innerWidth - 10))}px`
                : ''
            const computed = getComputedStyle(floating)
            const panel = { width: parseFloat(computed.width), height: parseFloat(computed.height) }
            const opposite = { left: 'right', right: 'left', top: 'bottom', bottom: 'top' }[side]
            if (horizontal && panel.width > Math.max(space.left, space.right)) {
                placedSide =
                    space.bottom >= panel.height || space.bottom >= space.top ? 'bottom' : 'top'
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
                const viewport = alignedItem.closest('.ui-scroll-area-viewport')
                const popupBox = floating.getBoundingClientRect()
                const row = alignedItem.getBoundingClientRect()
                const center = anchor.top + anchor.height / 2
                const rowCenter = row.top - popupBox.top + viewport.scrollTop + row.height / 2
                const above = Math.min(rowCenter, center - 5)
                // The combobox can have a fixed search field above its scrollable options.
                const contentHeight =
                    popupBox.height - viewport.clientHeight + viewport.scrollHeight
                const below = Math.min(contentHeight - rowCenter, innerHeight - center - 5)
                setPixels('--available-height', above + below)
                panel.height = parseFloat(computed.height)
                const before = Math.min(above, Math.max(panel.height / 2, panel.height - below))
                top = center - before
                const scroll = Math.round(rowCenter - before)
                // Keep the opening alignment without undoing subsequent wheel or keyboard scrolling.
                if (viewport !== alignedViewport || scroll !== alignmentScroll)
                    viewport.scrollTop = scroll
                alignedViewport = viewport
                alignmentScroll = scroll
                const value = trigger.querySelector('.ui-select-value')
                const text = alignedItem.querySelector('.ui-option-text')
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
            const clampedLeft = clamp(left, 5, innerWidth - panel.width - 5)
            const clampedTop = clamp(top, 5, innerHeight - panel.height - 5)
            const preserveFraction = alignedItem || this.preservePositionFraction
            Object.assign(style, {
                left: `${preserveFraction ? clampedLeft : Math.round(clampedLeft * ratio) / ratio}px`,
                top: `${preserveFraction ? clampedTop : Math.round(clampedTop * ratio) / ratio}px`,
            })
            const arrow = floating.querySelector('[data-ui-arrow]')
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
                '--origin-x',
                placedSide === 'left'
                    ? panel.width + sideOffset
                    : placedSide === 'right'
                      ? -sideOffset
                      : arrowX,
            )
            setPixels(
                '--origin-y',
                pointerOffset
                    ? 0
                    : placedSide === 'top'
                      ? panel.height + sideOffset
                      : placedSide === 'bottom'
                        ? -sideOffset
                        : arrowY,
            )
            setPixels('--arrow-x', arrowX)
            setPixels('--arrow-y', arrowY)
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
        const operation = evaluate(expression)
        cleanup(
            bindSlotControl(
                element,
                (control) => bindTrigger(Alpine, element, control, operation, evaluate),
                () => JSON.parse(element.dataset.uiErrors).control,
            ),
        )
    })
}

function bindTrigger(Alpine, element, control, operation, evaluate) {
    const panelRole = () => evaluate('panel?.role')
    if (operation === 'navigation') {
        const value = element.dataset.uiNavigationValue
        const scope = { navigationValue: value }
        return Alpine.bind(control, {
            ':aria-controls': () => evaluate('opened ? panel?.id : null'),
            ':popovertarget': () => evaluate('opened ? panel?.id : null'),
            ':aria-expanded': () =>
                String(evaluate('opened && active === navigationValue', { scope })),
            ':data-panel-open': () =>
                evaluate('opened && active === navigationValue', { scope }) ? '' : null,
            '@pointerenter': (event) => {
                if (event.pointerType !== 'touch') evaluate('schedule(navigationValue)', { scope })
            },
            '@mousemove': () => evaluate('keyboardFocus = false'),
            '@click': (event) => {
                event.preventDefault()
                evaluate('toggleItem(navigationValue, navigationKeyboard)', {
                    scope: { ...scope, navigationKeyboard: event.detail === 0 },
                })
            },
        })
    }
    if (panelRole() === 'tooltip') {
        const describedby = control.getAttribute('aria-describedby')
        return Alpine.bind(control, {
            ':aria-describedby': () =>
                [describedby, evaluate('panel?.id')].filter(Boolean).join(' '),
        })
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
            evaluate(`${operation}(${operation === 'close' ? 'true' : pointerMenu ? 'false' : ''})`)
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
            return operation === 'close' ||
                evaluate("$root.hasAttribute('data-ui-static-expanded')")
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
    if (element.hasAttribute('data-ui-media-trigger'))
        return bindMediaTrigger(Alpine, control, bindings, evaluate)
    const unbind = Alpine.bind(control, bindings)
    queueMicrotask(() => {
        const panel = evaluate('panel')
        if (operation !== 'close' && panel?.id) control.setAttribute('aria-controls', panel.id)
    })
    return unbind
}

function bindMediaTrigger(Alpine, control, bindings, evaluate) {
    bindings[':aria-controls'] = () => evaluate("$id('ui-dialog')")
    if (!control.matches('button, a[href], input, select, textarea, summary')) {
        if (!control.hasAttribute('role')) bindings.role = 'button'
        if (!control.hasAttribute('tabindex')) bindings.tabindex = '0'
        const activate = () =>
            control.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }))
        bindings['@keydown.enter.self.prevent'] = activate
        bindings['@keydown.space.self.prevent'] = activate
    }
    // The slot wrapper can stay in the DOM when a Livewire update gives it a new button.
    const attributes = Object.keys(bindings)
        .filter((name) => !name.startsWith('@'))
        .map((name) => name.replace(/^:/, ''))
        .map((name) => [name, control.getAttribute(name)])
    const unbind = Alpine.bind(control, bindings)
    return () => {
        unbind()
        for (const [name, value] of attributes) {
            if (value === null) control.removeAttribute(name)
            else control.setAttribute(name, value)
        }
    }
}
