/*
 * The drag handle: a grip beside the block under the pointer (a mouse or a pen).
 * Dragging it moves the block, as ProseMirror moves a dragged node; clicking it selects
 * the block.
 */

import { Extension } from '@tiptap/core'
import { NodeSelection, Plugin } from '@tiptap/pm/state'

// The top-level block at a height of the document, as its position, or null.
function blockAt(view, y) {
    const box = view.dom.getBoundingClientRect()
    const found = view.posAtCoords({ left: box.left + Math.min(48, box.width / 2), top: y })

    if (! found) return null

    const $position = view.state.doc.resolve(found.inside >= 0 ? found.inside : found.pos)

    return $position.depth ? $position.before(1) : found.inside >= 0 && $position.nodeAfter ? found.inside : null
}

export function dragHandle(handle) {
    return Extension.create({
        name: 'dragHandle',

        addProseMirrorPlugins: () => [
            new Plugin({
                view(view) {
                    const container = view.dom.parentElement
                    let target = null

                    const hide = () => {
                        handle.hidden = true
                        target = null
                    }

                    const show = position => {
                        const dom = view.nodeDOM(position)

                        if (! (dom instanceof HTMLElement) || ! view.editable) return hide()

                        target = position
                        handle.style.top = `${dom.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop}px`
                        handle.hidden = false
                    }

                    // Touch has nothing to point with: no handle for it.
                    const move = event => {
                        if (event.pointerType === 'touch') return hide()
                        if (event.target === handle || handle.contains(event.target)) return

                        const position = blockAt(view, event.clientY)

                        position === null ? hide() : show(position)
                    }

                    const select = () => {
                        if (target === null) return null

                        const selection = NodeSelection.create(view.state.doc, target)

                        view.dispatch(view.state.tr.setSelection(selection))

                        return selection
                    }

                    const start = event => {
                        const selection = select()

                        if (! selection || ! event.dataTransfer) return

                        const slice = selection.content()
                        const { dom, text } = view.serializeForClipboard(slice)

                        event.dataTransfer.clearData()
                        event.dataTransfer.setData('text/html', dom.innerHTML)
                        event.dataTransfer.setData('text/plain', text)
                        event.dataTransfer.effectAllowed = 'copyMove'
                        event.dataTransfer.setDragImage(view.nodeDOM(target), 0, 0)
                        view.dragging = { slice, move: true, node: selection }
                    }

                    const click = () => {
                        select()
                        view.focus()
                    }

                    handle.hidden = true
                    container.append(handle)
                    container.addEventListener('pointermove', move)
                    container.addEventListener('pointerleave', hide)
                    handle.addEventListener('dragstart', start)
                    handle.addEventListener('click', click)

                    return {
                        // The block it was beside may have moved: it comes back on the next move.
                        update: (_, previous) => view.state.doc.eq(previous.doc) || hide(),
                        destroy: () => {
                            container.removeEventListener('pointermove', move)
                            container.removeEventListener('pointerleave', hide)
                            handle.removeEventListener('dragstart', start)
                            handle.removeEventListener('click', click)
                            handle.remove()
                        },
                    }
                },
            }),
        ],
    })
}
