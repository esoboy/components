import { DOMParser, DOMSerializer, Fragment, Slice } from '@tiptap/pm/model'
import { closeHistory } from '@tiptap/pm/history'
import { NodeSelection, Selection } from '@tiptap/pm/state'
import { sanitizeHTML } from './content'
import { fileKind, mediaBatchKind, mediaPresentation, nameFromURL } from './media'

let sequence = 0

function clipboardFiles(data) {
    const files = Array.from(data.files ?? [])
    if (files.length) return files
    return Array.from(data.items ?? [])
        .filter((item) => item.kind === 'file')
        .map((item) => item.getAsFile())
        .filter(Boolean)
}

function matchImageFiles(images, files, base) {
    const remaining = files.filter((file) => fileKind(file.type) === 'image')
    const matches = new Map()
    if (!remaining.length) return matches
    const embedded = []
    const external = []
    for (const image of images) {
        const src = image.getAttribute('src')?.trim()
        if (!src || /^(?:data|blob|cid|file):/i.test(src)) {
            embedded.push(image)
            continue
        }
        const url = URL.parse(src, base)
        const name = url && nameFromURL(url.href)
        const index = remaining.findIndex((file) => file.name === name)
        if (index === -1) external.push(image)
        else matches.set(image, remaining.splice(index, 1)[0])
    }
    // If the clipboard supplies only some of the files, use their bytes for the
    // embedded images first. External images can still be imported by address.
    const unmatched =
        remaining.length < embedded.length + external.length
            ? [...embedded, ...external]
            : images.filter((image) => !matches.has(image))
    remaining.forEach((file, index) => {
        if (unmatched[index]) matches.set(unmatched[index], file)
    })
    return matches
}

function imageSource(image, base, index) {
    const src = image.getAttribute('src')?.trim()
    if (!src) return null
    if (/^data:image\//i.test(src)) {
        const comma = src.indexOf(',')
        const header = src.slice(5, comma)
        if (comma !== -1 && /;base64$/i.test(header)) {
            const type = header.split(';')[0].toLowerCase()
            const ext = type
                .slice(6)
                .split('+')[0]
                .replace(/^jpeg$/, 'jpg')
            try {
                const binary = atob(src.slice(comma + 1))
                const bytes = new Uint8Array(binary.length)
                for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i)
                return new File([bytes], `pasted-image-${index + 1}.${ext}`, { type })
            } catch {
                // Let the application report an unreadable image through the same request.
            }
        }
        return src
    }
    const url = URL.parse(src, base)
    return url && ['https:', 'http:', 'blob:'].includes(url.protocol) ? url.href : null
}

// The clipboard HTML stays inert until the application replies. Only the returned
// media nodes can enter the document. The surrounding markup uses the normal import rules.
function pasteSlice(view, template, slots, nodes, from) {
    const { schema } = view.state
    if (!template)
        return new Slice(Fragment.fromArray(nodes.map((node) => schema.nodeFromJSON(node))), 0, 0)

    const serializer = DOMSerializer.fromSchema(schema)
    const document = template.content.ownerDocument
    for (const [index, element] of slots.entries()) {
        const node = nodes[index]
        if (node) {
            const attrs = mediaPresentation(node.attrs, {
                alt: element.getAttribute('alt'),
                align: element.getAttribute('data-align'),
                displaySize: element.getAttribute('data-display-size'),
            })
            element.replaceWith(serializer.serializeNode(schema.node('image', attrs), { document }))
        } else {
            element.remove()
        }
    }
    for (const node of nodes.slice(slots.length)) {
        template.content.append(serializer.serializeNode(schema.nodeFromJSON(node), { document }))
    }
    template.innerHTML = sanitizeHTML(template.innerHTML)
    if (
        !nodes.length &&
        !template.content.textContent.trim() &&
        !template.content.querySelector(
            'img[src], table, hr, iframe, audio, video, a[data-type="file"]',
        )
    ) {
        return Slice.empty
    }
    return DOMParser.fromSchema(schema).parseSlice(template.content, {
        context: view.state.doc.resolve(from),
        preserveWhitespace: true,
    })
}

export function handleMediaPaste(view, event, uploadImages, select) {
    const data = event.clipboardData
    if (!view.editable || !data) return false
    const files = clipboardFiles(data).filter(
        (file) => uploadImages || fileKind(file.type) !== 'image',
    )
    const html = data.getData('text/html')
    if (!files.length && (!uploadImages || !/<img[\s/>]/i.test(html))) return false

    let template = null
    const slots = []
    const items = []
    if (html) {
        template = document.createElement('template')
        template.innerHTML = html
        if (uploadImages) {
            const base =
                URL.parse(
                    template.content.querySelector('base[href]')?.getAttribute('href') || '',
                    document.baseURI,
                ) ?? document.baseURI
            const images = Array.from(template.content.querySelectorAll('img'))
            const imageFiles = matchImageFiles(images, files, base)
            for (const image of images) {
                const item = imageFiles.get(image) ?? imageSource(image, base, slots.length)
                if (item) {
                    slots.push(image)
                    items.push(item)
                } else {
                    image.remove()
                }
            }
        }
    }
    const used = new Set(items)
    items.push(...files.filter((file) => !used.has(file)))
    if (!items.length) return false

    const { doc, selection } = view.state
    const original = selection.empty ? null : doc.slice(selection.from, selection.to)
    const id = ++sequence
    const paste = {
        id,
        apply(editor, { from, to }, nodes) {
            // If the user edited the selected text while an upload ran, keep their edit.
            if (original && !original.eq(editor.state.doc.slice(from, to))) to = from
            const slice = pasteSlice(editor.view, template, slots, nodes, from)
            if (!slice.size) return
            const tr = closeHistory(editor.state.tr)
            const atCaret = from === tr.selection.from && to === tr.selection.to
            if (atCaret) tr.replaceSelection(slice)
            else tr.replaceRange(from, to, slice)
            editor.view.dispatch(
                tr
                    .setMeta('editor-media-paste', id)
                    .setMeta('paste', true)
                    .setMeta('uiEvent', 'paste'),
            )
            // Tiptap adds a trailing paragraph after a block image. Put the caret
            // there so typing or a second paste continues after the uploaded image.
            const state = editor.state
            if (atCaret && state.selection instanceof NodeSelection) {
                const next = Selection.findFrom(state.selection.$to, 1, true)
                if (next) editor.view.dispatch(state.tr.setSelection(next))
            }
        },
    }
    event.preventDefault()
    select(mediaBatchKind(items), {
        source: 'paste',
        files: items.filter((item) => typeof item !== 'string'),
        items,
        paste,
    })
    return true
}
