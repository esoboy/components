/*
 * Images from elsewhere: addresses on another host than the page's (or the app's own,
 * data-image-hosts), as pasted ones are. While the app imports them, they show as
 * importing; its answer takes their place wherever they are by then.
 */

import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

export const importsKey = new PluginKey('editorImports')

// Whether an image address is on another host than the page or the app's own.
export function external(src, hosts) {
    try {
        const url = new URL(src, document.baseURI)

        return ['http:', 'https:'].includes(url.protocol) && url.host !== location.host && ! hosts.includes(url.host)
    } catch {
        return false
    }
}

// The external image addresses in a fragment or a document, each once.
export function externalImages(fragment, hosts) {
    const found = new Set()

    fragment.descendants(node => {
        if (node.type.name === 'image' && node.attrs.src && external(node.attrs.src, hosts)) found.add(node.attrs.src)
    })

    return [...found]
}

function decorate(doc, pending) {
    const decorations = []

    if (pending.size) {
        doc.descendants((node, position) => {
            if (node.type.name === 'image' && pending.has(node.attrs.src)) decorations.push(Decoration.node(position, position + node.nodeSize, { 'data-editor-importing': '' }))
        })
    }

    return DecorationSet.create(doc, decorations)
}

export const Imports = Extension.create({
    name: 'imports',

    addCommands: () => ({
        // The images waiting to be imported, by address.
        setImporting: pending => ({ tr, dispatch }) => {
            if (dispatch) tr.setMeta(importsKey, new Set(pending))

            return true
        },
    }),

    addProseMirrorPlugins: () => [
        new Plugin({
            key: importsKey,
            state: {
                init: () => ({ pending: new Set(), decorations: DecorationSet.empty }),
                apply(tr, previous) {
                    const pending = tr.getMeta(importsKey) ?? previous.pending

                    if (pending === previous.pending && ! tr.docChanged) return previous

                    return { pending, decorations: decorate(tr.doc, pending) }
                },
            },
            props: {
                decorations: state => importsKey.getState(state).decorations,
            },
        }),
    ],
})
