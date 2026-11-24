import { DOMSerializer } from '@tiptap/pm/model'
import { createDocument } from '@tiptap/core'
import contract from '../../content/schema.json'
import { readFrame } from './frame-attributes'

export { contract }
export const inputType = (value, type = 'auto') =>
    type === 'auto' ? (/^\s*<(?:[a-z][^>]*|!--)[>\s]/i.test(value) ? 'html' : 'markdown') : type

const tags = new Set([
    ...Object.values(contract.nodes).flat(),
    ...Object.values(contract.marks).flat(),
])
const patterns = Object.fromEntries(
    Object.entries(contract.styles).map(([key, value]) => [key, new RegExp(value, 'iu')]),
)

// The same allowlist drives PHP's RichContent and the editor's import boundary.
export function sanitizeHTML(value) {
    const doc = new DOMParser().parseFromString(value, 'text/html')
    for (const element of [...doc.body.querySelectorAll('*')]) {
        const tag = element.localName
        if (tag === 'iframe') {
            const attrs = readFrame(element)
            if (!attrs) element.remove()
            else {
                const frame = doc.createElement('iframe')
                for (const [key, val] of Object.entries(attrs)) frame.setAttribute(key, val)
                element.replaceWith(frame)
            }
            continue
        }
        if (!tags.has(tag)) {
            if (['script', 'style', 'object', 'embed', 'template'].includes(tag)) element.remove()
            else element.replaceWith(...element.childNodes)
            continue
        }
        const allowed = [...contract.globalAttributes, ...(contract.attributes[tag] ?? [])]
        for (const attribute of [...element.attributes]) {
            const { name, value } = attribute
            if (!allowed.includes(name)) {
                element.removeAttribute(name)
                continue
            }
            if (name === 'style') {
                const declarations = value
                    .split(';')
                    .map((part) => {
                        const index = part.indexOf(':')
                        const key = part.slice(0, index).trim().toLowerCase()
                        const val = part.slice(index + 1).trim()
                        return patterns[key]?.test(val) ? `${key}: ${val}` : ''
                    })
                    .filter(Boolean)
                if (declarations.length) element.setAttribute(name, declarations.join('; '))
                else element.removeAttribute(name)
            }
            if (name === 'href' || name === 'src') {
                try {
                    const url = new URL(value, location.href)
                    const protocols =
                        name === 'href'
                            ? ['http:', 'https:', 'mailto:', 'tel:']
                            : ['http:', 'https:']
                    if (!protocols.includes(url.protocol)) element.removeAttribute(name)
                } catch {
                    element.removeAttribute(name)
                }
            }
            if (name === 'class' && !/^language-[\w+-]+$/.test(value)) element.removeAttribute(name)
        }
        if (tag === 'a') element.setAttribute('rel', 'noopener noreferrer')
        if (tag === 'input') {
            element.setAttribute('type', 'checkbox')
            element.setAttribute('disabled', '')
        }
    }
    return doc.body.innerHTML
}

function nodeHTML(editor, node) {
    const container = document.createElement('div')
    container.append(DOMSerializer.fromSchema(editor.schema).serializeNode(node))
    // GFM otherwise treats audio, video and attachment anchors as inline HTML.
    return ['audio', 'video', 'file'].includes(node.type.name)
        ? `<div>${container.innerHTML}</div>`
        : container.innerHTML
}

function parseContent(editor, value, type, t) {
    if (typeof value !== 'string') throw new TypeError(t('contentTypeError'))
    if (new TextEncoder().encode(value).length > contract.maxLength)
        throw new RangeError(t('contentTooLong'))
    const html =
        inputType(value, type) === 'html'
            ? value
            : (() => {
                  const doc = editor.schema.nodeFromJSON(
                      editor.storage.markdown.manager.parse(value),
                  )
                  const container = document.createElement('div')
                  container.append(
                      DOMSerializer.fromSchema(editor.schema).serializeFragment(doc.content),
                  )
                  return container.innerHTML
              })()
    return createDocument(sanitizeHTML(html), editor.schema)
}

export function setContent(editor, value, type, t, addToHistory) {
    editor
        .chain()
        .setContent(parseContent(editor, value, type, t), { emitUpdate: false })
        .setMeta('addToHistory', addToHistory)
        .run()
}

// Separate blocks for reading without changing whitespace inside paragraphs or code.
export function sourceText(editor) {
    const fragment = DOMSerializer.fromSchema(editor.schema).serializeFragment(
        editor.state.doc.content,
        {
            document: document.implementation.createHTMLDocument(),
        },
    )
    return Array.from(fragment.children, (block) => block.outerHTML).join('\n')
}

export function applySource(editor, value, t) {
    const next = parseContent(editor, value, 'html', t)
    const { state } = editor,
        before = state.doc.content,
        after = next.content
    const start = before.findDiffStart(after)
    if (start == null) return
    const { a, b } = before.findDiffEnd(after)
    const overlap = Math.max(0, start - Math.min(a, b))
    // A small source edit should leave unrelated players, selections and undo steps in place.
    const tr = state.tr.replace(start, a + overlap, next.slice(start, b + overlap))
    editor.view.dispatch(tr.doc.eq(next) ? tr : state.tr.replaceWith(0, before.size, after))
}

// Markdown only when parsing it recreates the exact supported node. Richer blocks
// use embedded HTML, retaining marks, IDs, dimensions, media and merged cells.
// ProseMirror nodes are immutable: cache unchanged blocks across keystrokes.
export function serializer(editor) {
    const cache = new WeakMap()
    return (type) => {
        if (type === 'html') return editor.getHTML()
        const blocks = []
        editor.state.doc.forEach((node) => {
            if (!cache.has(node)) {
                let markdown
                let standard = contract.markdownNodes.includes(node.type.name)
                node.descendants((child) => {
                    if (!contract.markdownNodes.includes(child.type.name)) standard = false
                })
                if (['bulletList', 'orderedList'].includes(node.type.name)) {
                    node.descendants((child) => {
                        if (['bulletList', 'orderedList'].includes(child.type.name))
                            standard = false
                    })
                }
                if (standard) {
                    try {
                        const manager = editor.storage.markdown.manager
                        const candidate = manager.serialize({
                            type: 'doc',
                            content: [node.toJSON()],
                        })
                        const parsed = editor.schema.nodeFromJSON(manager.parse(candidate))
                        if (parsed.childCount === 1 && parsed.firstChild.eq(node))
                            markdown = candidate
                    } catch {
                        /* HTML is the lossless representation for unsupported Markdown. */
                    }
                }
                cache.set(node, markdown ?? nodeHTML(editor, node))
            }
            blocks.push(cache.get(node))
        })
        return blocks.join('\n\n')
    }
}
