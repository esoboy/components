export function observeChildren(root, update, attributeFilter) {
    const observer = new MutationObserver(update)
    observer.observe(root, { childList: true, subtree: true, attributeFilter })
    return () => observer.disconnect()
}

export function slotControl(element) {
    let control = element?.firstElementChild
    if (
        element?.hasAttribute('data-ui-media-trigger') &&
        (!control ||
            control.nextElementSibling ||
            [...element.childNodes].some(
                (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim(),
            ))
    )
        return element
    if (!control || control.nextElementSibling) return null
    while (control?.matches('[data-ui-trigger],[data-ui-trigger-proxy]')) {
        if (control.hasAttribute('data-ui-trigger-proxy'))
            control = control.querySelector('[data-ui-trigger]')
        control = control?.firstElementChild
    }
    return control
}

export function bindSlotControl(element, bind, missingControlMessage) {
    let current = slotControl(element)
    if (!current) throw new Error(missingControlMessage())
    let release = bind(current)
    const disconnect = observeChildren(element, () => {
        const next = slotControl(element)
        if (next === current) return
        release?.()
        current = next
        release = next && bind(next)
    })
    return () => {
        disconnect()
        release?.()
    }
}
