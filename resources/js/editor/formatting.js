import { Table } from '@tiptap/extension-table'
import { closeHistory } from '@tiptap/pm/history'
import { CellSelection } from '@tiptap/pm/tables'

// TableKit normally writes a style on every table and column, even without column widths.
// Remember whether the *input* used inline sizing so clean HTML can be reopened losslessly.
export const ContentTable = Table.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            inlineSizing: {
                default: true,
                rendered: false,
                parseHTML: (el) =>
                    el.hasAttribute('style') ||
                    Boolean(el.querySelector(':scope > colgroup > col[style]')),
            },
        }
    },
    renderHTML(props) {
        if (props.node.attrs.inlineSizing) return this.parent(props)
        const cols = []
        props.node.firstChild?.forEach(({ attrs: { colspan, colwidth } }) => {
            for (let i = 0; i < colspan; i++)
                cols.push(['col', colwidth?.[i] ? { width: colwidth[i] } : {}])
        })
        return ['table', props.HTMLAttributes, ['colgroup', ...cols], ['tbody', 0]]
    },
}).configure({ resizable: true, allowTableNodeSelection: true })

export function clearFormatting(editor) {
    return editor
        .chain()
        .focus()
        .command(({ tr }) => {
            const { selection } = tr
            const { $from } = selection
            const fullTable =
                selection instanceof CellSelection &&
                selection.isRowSelection() &&
                selection.isColSelection()
                    ? selection.$anchorCell.node(-1)
                    : null
            const ranges =
                selection.empty && $from.parent.isTextblock
                    ? [{ from: $from.start(), to: $from.end() }]
                    : selection.ranges.map((range) => ({
                          from: range.$from.pos,
                          to: range.$to.pos,
                      }))
            closeHistory(tr)
            for (const { from, to } of ranges) {
                if (selection.empty) tr.removeMark(from, to)
                tr.doc.nodesBetween(from, to, (node, pos) => {
                    // These node attributes emit inline CSS independently of text marks.
                    // Column widths, cell spans and media metadata are still content.
                    const attrs = node.attrs
                    const covered = from <= pos + 1 && to >= pos + node.nodeSize - 1
                    if (attrs.textAlign != null) tr.setNodeAttribute(pos, 'textAlign', null)
                    if (covered && attrs.listStyle != null)
                        tr.setNodeAttribute(pos, 'listStyle', null)
                    if (['tableCell', 'tableHeader'].includes(node.type.name) && attrs.align) {
                        tr.setNodeAttribute(pos, 'align', null)
                    }
                    if (
                        node.type.name === 'table' &&
                        attrs.inlineSizing &&
                        (covered || node === fullTable)
                    ) {
                        tr.setNodeAttribute(pos, 'inlineSizing', false)
                    }
                })
            }
            if (selection.empty) tr.setStoredMarks([])
            return true
        })
        .unsetAllMarks()
        .clearNodes()
        .run()
}
