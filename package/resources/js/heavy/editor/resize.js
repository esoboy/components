/*
 * Images, videos and embeds while editing: the media in a box as wide as its size and
 * placed by its alignment, with handles on the sides it can grow from (both when
 * centered). Dragging one sets the size in px, or 100% at the full width; the content
 * written keeps rendering from the node, so the box is the editor's alone.
 */

const JUSTIFY = { left: 'flex-start', center: 'center', right: 'flex-end' }

/**
 * A node view for a media node: tag is its element, attributes(node) what that element
 * carries besides its style, style(node) the style of the element in its box.
 */
export function resizable(tag, attributes, style) {
    return ({ node, getPos, editor }) => {
        const dom = document.createElement('div')
        const box = document.createElement('div')
        const media = document.createElement(tag)
        const handles = ['left', 'right'].map(side => {
            const handle = document.createElement('span')

            handle.dataset.editorResize = side
            handle.setAttribute('aria-hidden', 'true')

            return handle
        })

        dom.dataset.editorMedia = node.type.name
        box.dataset.editorMediaBox = ''
        media.draggable = false
        box.append(media, ...handles)
        dom.append(box)

        const render = () => {
            const align = node.attrs.align ?? 'left'

            dom.style.justifyContent = JUSTIFY[align] ?? JUSTIFY.left
            dom.dataset.align = align
            box.style.width = node.attrs.size ?? (tag === 'iframe' ? '100%' : '')

            for (const [name, value] of Object.entries(attributes(node))) {
                if (value == null || value === false) media.removeAttribute(name)
                else if (media.getAttribute(name) !== String(value)) media.setAttribute(name, value === true ? '' : value)
            }

            media.style.cssText = style(node)
        }

        const start = event => {
            if (! editor.isEditable || event.button > 0) return

            const handle = event.currentTarget
            const from = event.clientX
            const width = box.getBoundingClientRect().width
            const max = dom.clientWidth
            const grows = (handle.dataset.editorResize === 'left' ? -1 : 1) * (dom.dataset.align === 'center' ? 2 : 1)

            event.preventDefault()
            event.stopPropagation()
            handle.setPointerCapture?.(event.pointerId)
            dom.dataset.resizing = ''

            const move = moved => {
                box.style.width = `${Math.round(Math.min(max, Math.max(48, width + grows * (moved.clientX - from))))}px`
            }

            const end = () => {
                handle.removeEventListener('pointermove', move)
                handle.removeEventListener('pointerup', end)
                handle.removeEventListener('pointercancel', end)
                delete dom.dataset.resizing

                const position = getPos()
                const size = parseFloat(box.style.width) >= max ? '100%' : box.style.width

                if (typeof position !== 'number' || size === node.attrs.size) return render()

                editor.view.dispatch(editor.state.tr.setNodeMarkup(position, undefined, { ...node.attrs, size }))
            }

            handle.addEventListener('pointermove', move)
            handle.addEventListener('pointerup', end)
            handle.addEventListener('pointercancel', end)
        }

        handles.forEach(handle => handle.addEventListener('pointerdown', start))
        render()

        return {
            dom,
            update(next) {
                if (next.type !== node.type) return false

                node = next
                if (! ('resizing' in dom.dataset)) render()

                return true
            },
            // The handles are the view's own; ProseMirror leaves their events and its changes be.
            stopEvent: event => !! event.target.closest?.('[data-editor-resize]'),
            ignoreMutation: mutation => mutation.type !== 'selection',
        }
    }
}
