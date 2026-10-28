/*
 * What [data-editor-command] buttons and menu items do: run(chain, value) builds the
 * command, active(editor, value) says whether it is on at the selection. A value comes
 * from data-editor-value (a heading's level, a color, a size, …).
 */

const toggle = (command, name, attributes) => ({ run: chain => chain[command](), active: editor => editor.isActive(name, attributes) })

// The textStyle attribute set, or unset ('' is the default).
const textStyle = attribute => ({
    run: (chain, value) => chain.setMark('textStyle', { [attribute]: value || null }).removeEmptyTextStyle(),
    active: (editor, value) => (editor.getAttributes('textStyle')[attribute] ?? '') === value,
})

const block = attribute => editor => editor.getAttributes('paragraph')[attribute] ?? editor.getAttributes('heading')[attribute]

// On a selected image, video or embed.
const media = attribute => ({
    run: (chain, value, editor) => {
        const node = editor.state.selection.node

        return node && attribute in node.attrs ? chain.updateAttributes(node.type.name, { [attribute]: value || null }) : chain.command(() => false)
    },
    active: (editor, value) => (editor.state.selection.node?.attrs[attribute] ?? '') === value,
})

export const COMMANDS = {
    'undo': { run: chain => chain.undo() },
    'redo': { run: chain => chain.redo() },

    'paragraph': { run: chain => chain.setParagraph(), active: editor => editor.isActive('paragraph') },
    'heading': { run: (chain, level) => chain.toggleHeading({ level: Number(level) }), active: (editor, level) => editor.isActive('heading', { level: Number(level) }) },

    'bold': toggle('toggleBold', 'bold'),
    'italic': toggle('toggleItalic', 'italic'),
    'underline': toggle('toggleUnderline', 'underline'),
    'strike': toggle('toggleStrike', 'strike'),
    'code': toggle('toggleCode', 'code'),
    'superscript': toggle('toggleSuperscript', 'superscript'),
    'subscript': toggle('toggleSubscript', 'subscript'),

    'color': textStyle('color'),
    'font-family': textStyle('fontFamily'),
    'font-size': textStyle('fontSize'),
    'highlight': {
        run: (chain, color) => color ? chain.setHighlight({ color }) : chain.unsetHighlight(),
        active: (editor, color) => color ? editor.isActive('highlight', { color }) : ! editor.isActive('highlight'),
    },
    'clear-formatting': { run: chain => chain.clearFormatting() },

    'text-align': {
        run: (chain, align) => chain.setTextAlign(align),
        active: (editor, align) => (block('textAlign')(editor) ?? 'left') === align,
    },
    'line-height': {
        run: (chain, height) => chain.setLineHeight(height || null),
        active: (editor, height) => (block('lineHeight')(editor) ?? '') === height,
    },
    'indent': { run: chain => chain.indent() },
    'outdent': { run: chain => chain.outdent() },
    'text-indent': { run: chain => chain.toggleTextIndent(), active: editor => !! editor.getAttributes('paragraph').textIndent },

    'bullet-list': toggle('toggleBulletList', 'bulletList'),
    'ordered-list': toggle('toggleOrderedList', 'orderedList'),
    'task-list': toggle('toggleTaskList', 'taskList'),
    'blockquote': toggle('toggleBlockquote', 'blockquote'),
    'code-block': toggle('toggleCodeBlock', 'codeBlock'),
    'horizontal-rule': { run: chain => chain.setHorizontalRule() },
    'details': { run: chain => chain.setDetails(), active: editor => editor.isActive('details') },

    'table': { run: chain => chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }) },
    'add-row-before': { run: chain => chain.addRowBefore() },
    'add-row-after': { run: chain => chain.addRowAfter() },
    'delete-row': { run: chain => chain.deleteRow() },
    'add-column-before': { run: chain => chain.addColumnBefore() },
    'add-column-after': { run: chain => chain.addColumnAfter() },
    'delete-column': { run: chain => chain.deleteColumn() },
    'merge-cells': { run: chain => chain.mergeCells() },
    'split-cell': { run: chain => chain.splitCell() },
    'header-row': { run: chain => chain.toggleHeaderRow() },
    'header-column': { run: chain => chain.toggleHeaderColumn() },
    'delete-table': { run: chain => chain.deleteTable() },

    'media-size': media('size'),
    'media-align': media('align'),
    'delete-node': { run: chain => chain.deleteSelection() },
}
