function canFocus(node) {
    return (
        !node.matches(':disabled,[aria-disabled="true"],[hidden]') &&
        !node.closest('[inert]') &&
        node.getClientRects().length > 0 &&
        getComputedStyle(node).visibility === 'visible'
    )
}

export function enabled(root, selector, owner) {
    return [...root.querySelectorAll(selector)].filter(
        (node) => (!owner || node.closest(owner) === root) && canFocus(node),
    )
}

export function setActiveOption(list, input, option) {
    const previous = list.querySelector('[role="option"][data-highlighted]')
    if (previous !== option) {
        previous?.removeAttribute('data-highlighted')
        option?.setAttribute('data-highlighted', '')
    }
    if (option?.id) input?.setAttribute('aria-activedescendant', option.id)
    else input?.removeAttribute('aria-activedescendant')
}

export function adjacentItem(items, active, forward) {
    const index = items.indexOf(active)
    return forward ? (items[index + 1] ?? items[0]) : (items[index - 1] ?? items.at(-1))
}

export function moveFocus(event, nodes, vertical = true, origin = document.activeElement) {
    const [backward, forward] = vertical
        ? ['ArrowUp', 'ArrowDown']
        : getComputedStyle(event.currentTarget).direction === 'rtl'
          ? ['ArrowRight', 'ArrowLeft']
          : ['ArrowLeft', 'ArrowRight']
    let target
    if (event.key === 'Home') target = nodes[0]
    else if (event.key === 'End') target = nodes.at(-1)
    else if (event.key === forward || event.key === backward)
        target = adjacentItem(nodes, origin, event.key === forward)
    if (target) {
        event.preventDefault()
        target.focus()
    }
    return target
}

export function moveGroupFocus(event, group, selector) {
    const root = event.currentTarget
    if (
        event.defaultPrevented ||
        !(event.key.startsWith('Arrow') || event.key === 'Home' || event.key === 'End') ||
        !event.target.matches(selector) ||
        event.target.closest(group) !== root
    )
        return
    return moveFocus(
        event,
        enabled(root, selector, group),
        (root.ariaOrientation ?? root.dataset.orientation) === 'vertical',
        event.target,
    )
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
    if (event.key === 'Tab') return this.close()
    if (event.key === 'ArrowLeft' && this.$root.hasAttribute('data-ui-submenu')) {
        event.preventDefault()
        event.stopPropagation()
        return this.close(true)
    }
    const typeahead =
        event.key.length === 1 && !event.ctrlKey && !event.metaKey && event.key !== ' '
    if (!typeahead && !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return
    const items = enabled(panel, '[role="option"],[role^="menuitem"],button', '[data-ui-panel]')
    if (typeahead) {
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
    } else if (moveFocus(event, items)) event.stopPropagation()
}
