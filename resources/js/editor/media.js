import { Node, ResizableNodeView, getRenderedAttributes } from '@tiptap/core'
import { NodeSelection } from '@tiptap/pm/state'
import { closeHistory } from '@tiptap/pm/history'
import Image from '@tiptap/extension-image'
import Audio from '@tiptap/extension-audio'
import contract from '../../content/schema.json'

export const isVisual = (kind) => kind === 'image' || kind === 'video'
export const mediaDisplaySizes = contract.mediaDisplaySizes
const hasDuration = (kind) => kind === 'audio' || kind === 'video'

export function fileKind(type) {
    const prefix = type?.split('/')[0]
    return ['image', 'audio', 'video'].includes(prefix) ? prefix : 'file'
}

export function mediaBatchKind(items) {
    const kinds = new Set(
        items.map((item) => (typeof item === 'string' ? 'image' : fileKind(item.type))),
    )
    return kinds.size === 1 ? [...kinds][0] : 'file'
}

export function mediaURL(value, t) {
    if (typeof value !== 'string' || !value.trim()) throw new Error(t('missingFileUrl'))
    const address = value.trim()
    const url = URL.parse(address, document.baseURI)
    if (!url || !['http:', 'https:'].includes(url.protocol)) throw new Error(t('invalidFileUrl'))
    // The Audio extension also accepts relative addresses starting with a slash or dot.
    return /^[\w.+-]+:|^[/.]/i.test(address) ? address : url.href
}

export function nameFromURL(url) {
    const name = new URL(url, document.baseURI).pathname.split('/').pop() || ''
    try {
        return decodeURIComponent(name)
    } catch {
        return name
    }
}

function attribute(name, htmlName, numeric = false) {
    return {
        default: null,
        parseHTML: (element) => {
            const value = element.getAttribute(htmlName)
            if (!numeric || value === null) return value
            const n = Number(value)
            const minimum = ['width', 'height', 'fileWidth', 'fileHeight'].includes(name) ? 1 : 0
            return value.trim() &&
                Number.isFinite(n) &&
                n >= minimum &&
                (name === 'seconds' || Number.isInteger(n))
                ? n
                : null
        },
        renderHTML: (attrs) => (attrs[name] == null ? {} : { [htmlName]: attrs[name] }),
    }
}

function attributes(kind) {
    const attrs = {
        name: attribute('name', 'data-name'),
        title: attribute('title', 'title'),
        alt: attribute('alt', kind === 'image' ? 'alt' : 'aria-label'),
        size: attribute('size', 'data-size', true),
        type: attribute('type', 'data-mime'),
        ext: attribute('ext', 'data-ext'),
    }
    if (isVisual(kind)) {
        attrs.width = attribute('width', 'width', true)
        attrs.height = attribute('height', 'height', true)
        attrs.fileWidth = attribute('fileWidth', 'data-file-width', true)
        attrs.fileHeight = attribute('fileHeight', 'data-file-height', true)
        attrs.align = attribute('align', 'data-align')
        attrs.displaySize = {
            ...attribute('displaySize', 'data-display-size'),
            parseHTML: (element) => {
                const size = element.getAttribute('data-display-size')
                return mediaDisplaySizes.includes(size) ? size : null
            },
        }
    }
    if (hasDuration(kind)) attrs.seconds = attribute('seconds', 'data-seconds', true)
    return attrs
}

function presetDimensions(size, { fileWidth = null, fileHeight = null }) {
    return {
        displaySize: size,
        width: size === 'original' ? fileWidth : null,
        height: size === 'original' ? fileHeight : null,
    }
}

export function mediaPresentation(file, { alt, align, displaySize }) {
    return {
        ...file,
        alt,
        align,
        ...(mediaDisplaySizes.includes(displaySize) ? presetDimensions(displaySize, file) : {}),
    }
}

function setVisualDimensions(editor, pos, dimensions) {
    if (!editor.isEditable || pos == null) return
    const node = editor.state.doc.nodeAt(pos)
    if (!isVisual(node?.type.name)) return
    const tr = closeHistory(editor.state.tr)
    tr.setNodeMarkup(pos, null, { ...node.attrs, ...dimensions })
    editor.view.dispatch(tr.setSelection(NodeSelection.create(tr.doc, pos)))
}

export function applyMediaSize(editor, size) {
    const { node, from } = editor.state.selection
    if (isVisual(node?.type.name) && node.attrs.displaySize !== size) {
        setVisualDimensions(editor, from, presetDimensions(size, node.attrs))
    }
    editor.commands.focus()
}

// Both visual nodes use Tiptap's resize engine. Only the final drag is a document edit.
function visualNodeView({ node, editor, getPos, HTMLAttributes }) {
    const element = document.createElement(node.type.name === 'image' ? 'img' : 'video')
    element.draggable = false
    if (node.type.name === 'video') {
        element.controls = true
        element.preload = 'metadata'
        // Native player controls may consume the click but still focus their video.
        element.addEventListener('focus', () => {
            const pos = getPos()
            if (editor.isEditable && pos != null) editor.commands.setNodeSelection(pos)
        })
    }
    let previous = {}
    function sync(attrs) {
        for (const key of Object.keys(previous)) {
            if (!(key in attrs)) element.removeAttribute(key)
        }
        for (const [key, value] of Object.entries(attrs)) {
            if (previous[key] !== value) element.setAttribute(key, value)
        }
        const size = attrs['data-display-size']
        const relative = size && size !== 'original'
        if (relative) view.wrapper.dataset.displaySize = size
        else delete view.wrapper.dataset.displaySize
        view.wrapper.style.width = ''
        view.dom.dataset.align = attrs['data-align'] || 'left'
        element.style.width = relative ? '100%' : attrs.width ? `${attrs.width}px` : ''
        element.style.height = ''
        element.style.aspectRatio =
            attrs.width && attrs.height ? `${attrs.width} / ${attrs.height}` : ''
        previous = attrs
    }
    const view = new ResizableNodeView({
        element,
        editor,
        node,
        getPos,
        options: {
            directions: ['left', 'right'],
            min: { width: 32, height: 18 },
            preserveAspectRatio: true,
        },
        onResize(width, height) {
            view.wrapper.style.width = `${width}px`
            element.style.height = ''
            element.style.aspectRatio = `${width} / ${height}`
        },
        onCommit(width, height) {
            if (!view.wrapper.style.width) return
            setVisualDimensions(editor, getPos(), {
                displaySize: null,
                width: Math.round(width),
                height: Math.round(height),
            })
        },
        onUpdate(current) {
            sync(getRenderedAttributes(current, editor.extensionManager.attributes))
            return true
        },
    })
    sync(HTMLAttributes)
    return view
}

export const MediaImage = Image.extend({
    addAttributes() {
        return { ...this.parent(), ...attributes('image') }
    },
    addNodeView() {
        return visualNodeView
    },
})

export const MediaAudio = Audio.extend({
    addAttributes() {
        return { ...this.parent(), ...attributes('audio') }
    },
})

export const Video = Node.create({
    name: 'video',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return { src: { default: null }, ...attributes('video') }
    },
    parseHTML() {
        return [{ tag: 'video[src]' }]
    },
    renderHTML({ HTMLAttributes }) {
        return ['video', { ...HTMLAttributes, controls: '', preload: 'metadata' }]
    },
    addNodeView() {
        return visualNodeView
    },
})

export const FileAttachment = Node.create({
    name: 'file',
    group: 'block',
    atom: true,
    draggable: true,
    // An attachment is a block; ordinary anchors continue to use the Link mark.
    addAttributes() {
        const attrs = attributes('file')
        return {
            src: attribute('src', 'href'),
            ...attrs,
            name: {
                ...attrs.name,
                parseHTML: (el) =>
                    el.getAttribute('data-name') ||
                    el.getAttribute('download') ||
                    (el.firstElementChild ?? el).textContent.trim() ||
                    nameFromURL(el.href),
            },
        }
    },
    parseHTML() {
        return [{ tag: 'a[data-type="file"][href]', priority: 1100 }]
    },
    renderHTML({ node, HTMLAttributes }) {
        const { name, title, src, size } = node.attrs
        const info = []
        if (title && name && title !== name) info.push(name)
        if (size != null) {
            const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB']
            const unit = Math.min(
                units.length - 1,
                Math.floor(Math.log(Math.max(1, size)) / Math.log(1024)),
            )
            info.push(`${Number((size / 1024 ** unit).toFixed(1))} ${units[unit]}`)
        }
        return [
            'a',
            {
                ...HTMLAttributes,
                'data-type': 'file',
                download: name || '',
                rel: 'noopener noreferrer',
            },
            ['span', title || name || src || this.options.fileLabel()],
            ...(info.length ? [['span', info.join(' · ')]] : []),
        ]
    },
})

function fileNodes(kind, results, source, t) {
    if (!Array.isArray(results)) throw new Error(t('invalidFileList'))
    return results.map((file) => {
        if (
            !file ||
            typeof file.name !== 'string' ||
            !file.name.trim() ||
            typeof file.type !== 'string' ||
            typeof file.ext !== 'string' ||
            (file.title != null && typeof file.title !== 'string')
        ) {
            throw new Error(t('invalidFileData'))
        }
        const nodeType = source === 'picker' ? kind : fileKind(file.type)
        if (nodeType !== 'file' && fileKind(file.type) !== nodeType)
            throw new Error(t('wrongFileType', { kind: t(nodeType) }))
        const attrs = {
            name: file.name,
            src: mediaURL(file.url, t),
            title: file.title ?? '',
            type: file.type,
            ext: file.ext,
        }
        const numbers = [
            'size',
            ...(isVisual(nodeType) ? ['width', 'height'] : []),
            ...(hasDuration(nodeType) ? ['seconds'] : []),
        ]
        for (const key of numbers) {
            const value = file[key] ?? null
            if (
                value !== null &&
                (!Number.isFinite(value) ||
                    value < 0 ||
                    (key !== 'seconds' && !Number.isInteger(value)) ||
                    (['width', 'height'].includes(key) && value === 0))
            )
                throw new Error(t('invalidFileField', { field: key }))
            attrs[key] = value
        }
        if (isVisual(nodeType)) {
            attrs.fileWidth = attrs.width
            attrs.fileHeight = attrs.height
        }
        return { type: nodeType, attrs }
    })
}

export function insertMedia(editor, range, nodes) {
    return editor
        .chain()
        .focus()
        .command(({ tr }) => {
            closeHistory(tr)
            return true
        })
        .insertContentAt(range, nodes)
        .run()
}

// Capture the target before handing focus to the application. Map it through edits
// while the picker is open, and retire it when its document or node disappears.
export function requestMedia(
    host,
    kind,
    { source = 'picker', files = [], items = files, position, replace = false, paste } = {},
) {
    const editor = host.editor
    const t = (key, values) => host.getMessage(key, values)
    if (!editor?.isEditable) return Promise.resolve()
    const { selection } = editor.state
    let from = position ?? selection.from
    let to = position ?? selection.to
    const target = replace && selection instanceof NodeSelection ? selection.node : null
    if (replace && target?.type.name !== kind) return Promise.resolve()
    if (!replace && !paste && position == null && selection instanceof NodeSelection) from = to
    const signal = host.mediaSession.signal
    return new Promise((resolve, reject) => {
        let active = true
        const finish = () => {
            active = false
            editor.off('transaction', map)
            signal.removeEventListener('abort', cancel)
        }
        const cancel = () => {
            finish()
            resolve()
        }
        const map = ({ transaction, appendedTransactions }) => {
            for (const tr of [transaction, ...appendedTransactions]) {
                if (!tr.docChanged) continue
                // At one caret, pasted batches keep invocation order even if uploads
                // finish in reverse. Subsequent typing stays after the pending paste.
                const previousPaste = tr.getMeta('editor-media-paste')
                const afterEarlierPaste = paste && previousPaste != null && previousPaste < paste.id
                const before = paste && from === to && !afterEarlierPaste
                const start = tr.mapping.mapResult(from, before ? -1 : 1)
                if (paste && previousPaste == null && start.deletedAcross) return cancel()
                if (target && (start.deleted || tr.doc.nodeAt(start.pos)?.type !== target.type))
                    return cancel()
                const end = tr.mapping.mapResult(to, -1)
                if (paste && from !== to && start.deleted && end.deleted) {
                    from = to = afterEarlierPaste ? end.pos : start.pos
                    continue
                }
                to = from === to ? start.pos : Math.max(start.pos, end.pos)
                from = start.pos
            }
        }
        const fail = (error) => {
            if (!active) return
            finish()
            if (paste && host.isConnected && editor.isEditable) {
                try {
                    paste.apply(editor, { from, to }, [])
                } catch (parseError) {
                    return reject(parseError)
                }
            }
            reject(error instanceof Error ? error : new Error(String(error || t('mediaFailed'))))
        }
        const accept = (results) => {
            if (!active) return
            try {
                const nodes = fileNodes(kind, results, source, t)
                if (!host.isConnected || !editor.isEditable || (!nodes.length && !paste))
                    return cancel()
                if (paste && nodes.length) {
                    if (nodes.length !== items.length) throw new Error(t('pasteOrder'))
                    if (
                        nodes.some(
                            (node, index) =>
                                node.type !== 'image' &&
                                (typeof items[index] === 'string' ||
                                    fileKind(items[index].type) === 'image'),
                        )
                    ) {
                        throw new Error(t('pasteImageType'))
                    }
                }
                if (target) {
                    const current = editor.state.doc.nodeAt(from)
                    nodes[0].attrs = mediaPresentation(nodes[0].attrs, current.attrs)
                }
                finish()
                if (paste) paste.apply(editor, { from, to }, nodes)
                else if (!insertMedia(editor, { from, to }, nodes))
                    throw new Error(t('cannotInsertFile'))
                resolve()
            } catch (error) {
                if (active) fail(error)
                else reject(error)
            }
        }
        editor.on('transaction', map)
        signal.addEventListener('abort', cancel, { once: true })
        const event = new CustomEvent('editor-media-select', {
            bubbles: true,
            composed: true,
            cancelable: true,
            detail: { kind, multiple: true, source, files, items, resolve: accept, reject: fail },
        })
        if (host.dispatchEvent(event)) fail(new Error(t('missingMediaHandler')))
    })
}
