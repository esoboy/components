import React, { useState } from 'react'
import { useTranslate } from './i18n'
import {
    CellSelection,
    columnIsHeader,
    moveTableRow,
    moveTableColumn,
    rowIsHeader,
    selectedRect,
    selectionCell,
} from '@tiptap/pm/tables'
import { Columns3, Rows3, Table, TableCellsMerge, TableCellsSplit, Trash } from 'lucide-react'
import { Field, Tool, Popover } from './controls'
import { ToolbarGroup, ToolbarSeparator } from './tiptap-ui/primitives'

function TableSize({ insert }) {
    const t = useTranslate()
    const [size, setSize] = useState({ rows: 3, cols: 3 })
    function navigate(event, rows, cols) {
        const offsets = {
            ArrowLeft: [0, -1],
            ArrowRight: [0, 1],
            ArrowUp: [-1, 0],
            ArrowDown: [1, 0],
        }
        if (!offsets[event.key]) return
        event.preventDefault()
        const [dr, dc] = offsets[event.key]
        const row = Math.max(1, Math.min(6, rows + dr)),
            col = Math.max(1, Math.min(8, cols + dc))
        event.currentTarget
            .closest('.table-grid')
            .querySelectorAll('button')
            [(row - 1) * 8 + col - 1].focus()
    }
    return (
        <>
            <div className="panel-heading">
                <strong>{t('insertTable')}</strong>
                <output>{t('tableDimensions', size)}</output>
            </div>
            <div className="table-grid" role="group" aria-label={t('chooseTableSize')}>
                {Array.from({ length: 48 }, (_, index) => {
                    const rows = Math.floor(index / 8) + 1,
                        cols = (index % 8) + 1
                    return (
                        <button
                            type="button"
                            key={index}
                            aria-label={t('tableCellPosition', { rows, cols })}
                            tabIndex={size.rows === rows && size.cols === cols ? 0 : -1}
                            data-filled={rows <= size.rows && cols <= size.cols}
                            onMouseEnter={() => setSize({ rows, cols })}
                            onFocus={() => setSize({ rows, cols })}
                            onKeyDown={(event) => navigate(event, rows, cols)}
                            onClick={() => insert(rows, cols)}
                        />
                    )
                })}
            </div>
            <details className="custom-size">
                <summary>{t('customTableSize')}</summary>
                <form
                    onSubmit={(event) => {
                        event.preventDefault()
                        const data = new FormData(event.currentTarget)
                        insert(Number(data.get('rows')), Number(data.get('cols')))
                    }}
                >
                    <div className="columns">
                        <Field
                            label={t('rows')}
                            name="rows"
                            type="number"
                            min="1"
                            max="100"
                            defaultValue="3"
                            required
                        />
                        <Field
                            label={t('columns')}
                            name="cols"
                            type="number"
                            min="1"
                            max="30"
                            defaultValue="3"
                            required
                        />
                    </div>
                    <button type="submit" className="primary">
                        {t('insertTable')}
                    </button>
                </form>
            </details>
        </>
    )
}

export function TablePicker({ editor }) {
    const t = useTranslate()
    return (
        <Popover
            label={t('table')}
            icon={Table}
            active={editor.isActive('table')}
            disabled={!editor.isEditable}
            className="table-picker"
        >
            {(close) => (
                <TableSize
                    insert={(rows, cols) => {
                        close()
                        editor
                            .chain()
                            .focus()
                            .insertTable({ rows, cols, withHeaderRow: true })
                            .run()
                    }}
                />
            )}
        </Popover>
    )
}

const axisActions = {
    row: ['addRowBefore', 'addRowAfter', 'toggleHeaderRow', 'deleteRow'],
    column: ['addColumnBefore', 'addColumnAfter', 'toggleHeaderColumn', 'deleteColumn'],
}
function TableAxis({ editor, axis, rect }) {
    const t = useTranslate()
    const row = axis === 'row'
    const { state } = editor
    const selected =
        state.selection instanceof CellSelection &&
        (row ? state.selection.isRowSelection() : state.selection.isColSelection())
    const header = row
        ? rowIsHeader(rect.map, rect.table, 0)
        : columnIsHeader(rect.map, rect.table, 0)
    const headerCommand = row ? 'toggleHeaderRow' : 'toggleHeaderColumn'
    return (
        <Popover
            icon={row ? Rows3 : Columns3}
            label={t(row ? 'rowActions' : 'columnActions')}
            text={t(axis)}
            active={selected || (header && (row ? rect.bottom === 1 : rect.right === 1))}
            className="action-menu"
        >
            {(close) => {
                const index = row ? rect.top : rect.left,
                    count = row ? rect.map.height : rect.map.width
                const move = row ? moveTableRow : moveTableColumn
                const run = (action) => {
                    close()
                    action()
                    editor.commands.focus()
                }
                return (
                    <>
                        <button
                            type="button"
                            data-active-state={selected ? 'on' : 'off'}
                            onClick={() =>
                                run(() => {
                                    const cell = selectionCell(state)
                                    const selection = row
                                        ? CellSelection.rowSelection(cell)
                                        : CellSelection.colSelection(cell)
                                    editor.view.dispatch(state.tr.setSelection(selection))
                                })
                            }
                        >
                            {t(row ? 'selectRow' : 'selectColumn')}
                        </button>
                        {axisActions[axis].map((command) => (
                            <button
                                key={command}
                                aria-pressed={command === headerCommand ? header : undefined}
                                type="button"
                                disabled={!editor.can()[command]()}
                                onClick={() => run(() => editor.commands[command]())}
                            >
                                {t(command)}
                            </button>
                        ))}
                        {[-1, 1].map((step, i) => (
                            <button
                                key={step}
                                type="button"
                                disabled={index + step < 0 || index + step >= count}
                                onClick={() =>
                                    run(() =>
                                        move({ from: index, to: index + step })(
                                            editor.state,
                                            editor.view.dispatch,
                                        ),
                                    )
                                }
                            >
                                {t(
                                    (row
                                        ? ['moveRowUp', 'moveRowDown']
                                        : ['moveColumnLeft', 'moveColumnRight'])[i],
                                )}
                            </button>
                        ))}
                    </>
                )
            }}
        </Popover>
    )
}
export function TableTools({ editor }) {
    const t = useTranslate()
    const rect = selectedRect(editor.state)
    const headerCell =
        editor.state.selection instanceof CellSelection
            ? rect.map
                  .cellsInRect(rect)
                  .every((pos) => rect.table.nodeAt(pos).type.name === 'tableHeader')
            : editor.isActive('tableHeader')
    return (
        <>
            <ToolbarGroup>
                <TableAxis editor={editor} axis="row" rect={rect} />
                <TableAxis editor={editor} axis="column" rect={rect} />
            </ToolbarGroup>
            <ToolbarSeparator />
            <ToolbarGroup>
                {[
                    [TableCellsMerge, t('mergeCells'), 'mergeCells'],
                    [TableCellsSplit, t('splitCell'), 'splitCell'],
                    [Table, t('toggleHeaderCell'), 'toggleHeaderCell'],
                    [Trash, t('deleteTable'), 'deleteTable'],
                ].map(([icon, label, command]) => (
                    <Tool
                        key={command}
                        icon={icon}
                        label={label}
                        active={command === 'toggleHeaderCell' ? headerCell : undefined}
                        disabled={!editor.can()[command]()}
                        onClick={() => editor.chain().focus()[command]().run()}
                    />
                ))}
            </ToolbarGroup>
        </>
    )
}
