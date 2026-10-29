/*
 * Placeholders, never part of the content: while the app uploads what was dropped or
 * pasted, one (the view's uploading template) stands where it will go, until the answer
 * comes or it is dismissed; where a pasted picture couldn't be read (images.js MISSING),
 * one (the missing template) says so, to upload it there or dismiss.
 */

import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'
import { MISSING } from './images.js'

export const uploadsKey = new PluginKey('editorUploads')

let missing = 0

export function uploads(templates) {
    return Extension.create({
        name: 'uploads',

        addCommands: () => ({
            addUpload: (id, position, kind = 'uploading') => ({ tr, dispatch }) => {
                if (dispatch) tr.setMeta(uploadsKey, { add: [{ id, position, kind }] })

                return true
            },
            removeUpload: id => ({ tr, dispatch }) => {
                if (dispatch) tr.setMeta(uploadsKey, { remove: id })

                return true
            },
        }),

        addProseMirrorPlugins: () => [
            new Plugin({
                key: uploadsKey,
                state: {
                    init: () => DecorationSet.empty,
                    apply(tr, set) {
                        const meta = tr.getMeta(uploadsKey)

                        set = set.map(tr.mapping, tr.doc)

                        for (const { id, position, kind } of meta?.add ?? []) {
                            const template = templates[kind]
                            const widget = () => {
                                const element = template.content.firstElementChild.cloneNode(true)

                                element.querySelectorAll('[data-editor-action]').forEach(control => control.setAttribute('data-editor-value', id))

                                return element
                            }

                            if (template) set = set.add(tr.doc, [Decoration.widget(Math.min(position, tr.doc.content.size), widget, { id, kind, side: -1 })])
                        }

                        if (meta?.remove) set = set.remove(set.find(undefined, undefined, spec => spec.id === meta.remove))

                        return set
                    },
                },
                props: {
                    decorations: state => uploadsKey.getState(state),
                },
                // What a paste or drop marked missing: the mark goes, a placeholder stands there.
                appendTransaction(transactions, previous, state) {
                    if (! templates.missing || ! transactions.some(tr => tr.docChanged && (tr.getMeta('paste') || ['paste', 'drop'].includes(tr.getMeta('uiEvent'))))) return null

                    const marks = []

                    state.doc.descendants((node, position) => {
                        if (! node.isText) return

                        for (let index = node.text.indexOf(MISSING); index >= 0; index = node.text.indexOf(MISSING, index + 1)) marks.push(position + index)
                    })

                    if (! marks.length) return null

                    const { tr } = state

                    marks.reverse().forEach(position => tr.delete(position, position + 1))

                    return tr.setMeta(uploadsKey, { add: marks.map(position => ({ id: `missing-${++missing}`, position: tr.mapping.map(position), kind: 'missing' })) })
                },
            }),
        ],
    })
}
