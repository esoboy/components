/*
 * Files on their way: while the app uploads what was dropped or pasted, a placeholder
 * (the view's template) stands where it will go, until the answer comes or it is
 * dismissed. It is a decoration, never part of the content.
 */

import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

export const uploadsKey = new PluginKey('editorUploads')

export function uploads(template) {
    return Extension.create({
        name: 'uploads',

        addCommands: () => ({
            addUpload: (id, position) => ({ tr, dispatch }) => {
                if (dispatch) tr.setMeta(uploadsKey, { add: { id, position } })

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

                        if (meta?.add) {
                            const widget = () => {
                                const element = template.content.firstElementChild.cloneNode(true)

                                element.querySelector('[data-editor-action="dismiss-upload"]')?.setAttribute('data-editor-value', meta.add.id)

                                return element
                            }

                            set = set.add(tr.doc, [Decoration.widget(Math.min(meta.add.position, tr.doc.content.size), widget, { id: meta.add.id, side: -1 })])
                        }

                        if (meta?.remove) set = set.remove(set.find(undefined, undefined, spec => spec.id === meta.remove))

                        return set
                    },
                },
                props: {
                    decorations: state => uploadsKey.getState(state),
                },
            }),
        ],
    })
}
