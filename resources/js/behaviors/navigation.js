export function enabled(root, selector) {
    return [...root.querySelectorAll(selector)].filter(
        (node) =>
            !node.matches(':disabled,[aria-disabled="true"],[hidden]') &&
            node.getClientRects().length,
    )
}

export function moveFocus(event, nodes, vertical = true, origin = document.activeElement) {
    const current = nodes.indexOf(origin)
    const last = nodes.length - 1
    const next = {
        [vertical ? 'ArrowDown' : 'ArrowRight']: current === last ? 0 : current + 1,
        [vertical ? 'ArrowUp' : 'ArrowLeft']: current <= 0 ? last : current - 1,
        Home: 0,
        End: last,
    }[event.key]
    const target = nodes[next]
    if (target) {
        event.preventDefault()
        target.focus()
    }
    return target
}

export function menubar() {
    return {
        navigate(event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return
            const menu = event.target.closest('[data-ui-overlay]')
            if (menu?.parentElement !== this.$root) return
            const triggers = enabled(
                this.$root,
                ':scope > [data-ui-overlay] > [data-ui-trigger] > *',
            )
            const panel = menu.querySelector('[popover]')
            const wasOpen = panel?.matches(':popover-open')
            const current = menu.querySelector('[data-ui-trigger]')?.firstElementChild
            if (!triggers.includes(current)) return
            if (wasOpen) panel.hidePopover()
            const trigger = moveFocus(event, triggers, false, current)
            event.stopPropagation()
            if (wasOpen)
                trigger?.dispatchEvent(
                    new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }),
                )
        },
    }
}

export function options(selector = '[role="option"],[role="menuitem"]') {
    return {
        navigate(event, origin) {
            if (
                event.target.matches('input,textarea') &&
                !['ArrowDown', 'ArrowUp', 'Escape'].includes(event.key)
            )
                return
            const nodes = enabled(this.$root, selector)
            moveFocus(event, nodes, true, origin)
        },
        navigateGrid(event, columns, rowEdges = false) {
            const nodes = [...event.currentTarget.querySelectorAll(selector)]
            const current = nodes.indexOf(event.target)
            if (current < 0) return
            const first = rowEdges ? current - (current % columns) : 0
            const next = {
                ArrowRight: current + 1,
                ArrowLeft: current - 1,
                ArrowDown: current + columns,
                ArrowUp: current - columns,
                Home: first,
                End: rowEdges ? first + columns - 1 : nodes.length - 1,
            }[event.key]
            if (next !== undefined) {
                event.preventDefault()
                nodes[next]?.focus()
            }
        },
    }
}

export function tabs(initial = null, orientation = 'horizontal') {
    return {
        active: initial === null ? null : String(initial),
        init() {
            this.active ??=
                this.$root.querySelector('[role="tab"]:not([disabled])')?.dataset.value ?? null
        },
        navigate(event) {
            const nodes = enabled(event.currentTarget, '[role="tab"]')
            const target = moveFocus(event, nodes, orientation === 'vertical')
            if (target) this.active = target.dataset.value
        },
    }
}

export function accordion() {
    return {
        navigate(event) {
            if (!event.target.matches('[data-slot="accordion-summary"]')) return
            const nodes = [
                ...this.$root.querySelectorAll('[data-slot="accordion-summary"]'),
            ].filter(
                (node) => !node.inert && node.closest('[data-slot="accordion"]') === this.$root,
            )
            moveFocus(event, nodes)
        },
    }
}
export function choiceGroup() {
    return {
        navigate(event) {
            if (!event.target.matches('input')) return
            const nodes = [...this.$root.querySelectorAll('input:not(:disabled)')]
            const target = moveFocus(event, nodes, this.$root.dataset.orientation === 'vertical')
            if (target?.type === 'radio') target.click()
        },
    }
}
