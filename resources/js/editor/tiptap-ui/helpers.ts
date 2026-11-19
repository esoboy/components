import { useCurrentEditor, type Editor } from '@tiptap/react'
import { NodeSelection } from '@tiptap/pm/state'

// EditorApp subscribes once per editor. The registry buttons share that render.
export function useTiptapEditor(provided?: Editor | null) {
    const { editor } = useCurrentEditor()
    return { editor: provided ?? editor }
}
export const isMarkInSchema = (name: string, editor: Editor) => name in editor.schema.marks
export const isNodeInSchema = (name: string, editor: Editor) => name in editor.schema.nodes
export const isExtensionAvailable = (editor: Editor | null, name: string) =>
    Boolean(editor?.extensionManager.extensions.some((extension) => extension.name === name))
export function isNodeTypeSelected(editor: Editor, types: string[]) {
    const { selection } = editor.state
    return selection instanceof NodeSelection && types.includes(selection.node.type.name)
}
export const parseShortcutKeys = ({ shortcutKeys = '' }: { shortcutKeys?: string }) =>
    shortcutKeys
        .replace('mod', /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘' : 'Ctrl')
        .split('+')
