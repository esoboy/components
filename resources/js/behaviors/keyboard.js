function canFocus(node) {
    return (
        !node.matches(':disabled,[aria-disabled="true"],[hidden]') &&
        !node.closest('[inert]') &&
        node.getClientRects().length > 0 &&
        getComputedStyle(node).visibility === 'visible'
    )
}

export function enabled(root, selector) {
    return [...root.querySelectorAll(selector)].filter(canFocus)
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
            const edge = event.key === 'Home' || event.key === 'End'
            const first = edge && rowEdges ? current - (current % columns) : 0
            const last =
                edge && rowEdges ? Math.min(first + columns, nodes.length) - 1 : nodes.length - 1
            const step = {
                ArrowRight: 1,
                ArrowLeft: -1,
                ArrowDown: columns,
                ArrowUp: -columns,
                Home: 1,
                End: -1,
            }[event.key]
            if (!step) return
            event.preventDefault()
            const start = edge ? (event.key === 'Home' ? first : last) : current + step
            for (let index = start; index >= first && index <= last; index += step) {
                const node = nodes[index]
                if (!canFocus(node)) continue
                node.focus()
                return
            }
        },
    }
}

export function navigatePopupList(event) {
    const panel = event.currentTarget
    if (
        event.defaultPrevented ||
        !['menu', 'listbox'].includes(panel.role) ||
        event.target.closest('[data-ui-panel]') !== panel
    )
        return
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
}
