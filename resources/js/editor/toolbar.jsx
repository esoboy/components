import React from 'react'
import {
    AppWindow,
    ArrowLeft,
    Baseline,
    ChevronDown,
    CodeXml,
    Ellipsis,
    Eraser,
    Highlighter,
    ListTree,
    Minus,
    PaintBucket,
    Quote,
    Search,
    SquareCode,
} from 'lucide-react'
import { MarkButton } from './tiptap-ui/mark-button'
import { UndoRedoButton } from './tiptap-ui/undo-redo-button'
import { TextAlignButton } from './tiptap-ui/text-align-button'
import { ListButton } from './tiptap-ui/list-button'
import { Toolbar, ToolbarGroup } from './tiptap-ui/primitives'
import { Field, Popover, Tool } from './controls'
import { FindPanel, LinkPopover } from './text-tools'
import { mediaIcons } from './media-tools'
import { useTranslate } from './i18n'
import { TablePicker } from './table'
import { clearFormatting } from './formatting'

const basicMarks = ['bold', 'italic', 'underline', 'strike', 'code']
export function Marks({ editor, types }) {
    return types.map((type) => <MarkButton key={type} editor={editor} type={type} />)
}

const typefaces = [
    'Arial',
    'Georgia',
    'Times New Roman',
    'Courier New',
    'Microsoft YaHei',
    'SimSun',
    'sans-serif',
    'serif',
    'monospace',
]
const sizes = [12, 14, 16, 18, 20, 24, 30, 36, 48, 60, 72].map((n) => `${n}px`)
const leading = ['1', '1.25', '1.5', '1.75', '2', '2.5', '3']

function StyleSelect({ editor, label, attr, set, unset, values, value }) {
    const t = useTranslate()
    return (
        <select
            className={attr}
            aria-label={label}
            title={label}
            data-active-state={value ? 'on' : 'off'}
            disabled={!editor.isEditable}
            value={value}
            onChange={({ target }) => {
                const chain = editor.chain().focus()
                if (target.value) chain[set](target.value).run()
                else chain[unset]().run()
            }}
        >
            <option value="">{label}</option>
            {value && !values.includes(value) && <option>{value}</option>}
            {values.map((item) => (
                <option key={item} value={item}>
                    {attr === 'fontFamily' ? t(item) : item}
                </option>
            ))}
        </select>
    )
}

function ColorControl({ editor, label, value, command, initial, icon: Icon }) {
    const color = value || initial
    return (
        <label
            className="color-tool"
            title={label}
            data-active-state={value ? 'on' : 'off'}
            style={{ '--swatch': color }}
        >
            <Icon size={16} aria-hidden="true" />
            <input
                type="color"
                aria-label={label}
                disabled={!editor.isEditable}
                value={color}
                onChange={({ target }) => editor.chain().focus()[command](target.value).run()}
            />
        </label>
    )
}

function MoreBlocks({ editor }) {
    const t = useTranslate()
    const details = editor.isActive('details')
    const codeBlock = editor.isActive('codeBlock')
    const language = codeBlock && editor.getAttributes('codeBlock').language
    const activeLanguage = language && editor.isActive('codeBlock', { language }) ? language : ''
    const blocks = [
        [SquareCode, t('codeBlock'), codeBlock, 'toggleCodeBlock'],
        [Minus, t('horizontalRule'), editor.isActive('horizontalRule'), 'setHorizontalRule'],
        [ChevronDown, t('details'), details, details ? 'unsetDetails' : 'setDetails'],
    ]
    const listStyle = editor.getAttributes('orderedList').listStyle
    const styledList = Boolean(listStyle && editor.isActive('orderedList', { listStyle }))
    return (
        <Popover
            label={t('moreBlocks')}
            icon={Ellipsis}
            active={styledList || blocks.some(([, , active]) => active)}
            disabled={!editor.isEditable}
            chevron={false}
            className="action-menu"
        >
            {(close) => (
                <>
                    {blocks.map(([icon, label, active, command]) => (
                        <Tool
                            key={label}
                            label={label}
                            icon={icon}
                            active={active}
                            onClick={() => {
                                close()
                                editor.chain().focus()[command]().run()
                            }}
                        >
                            {label}
                        </Tool>
                    ))}
                    {editor.isActive('orderedList') && (
                        <Field label={t('numberingStyle')}>
                            <select
                                value={listStyle ?? 'decimal'}
                                data-active-state={styledList ? 'on' : 'off'}
                                onChange={({ target }) =>
                                    editor.commands.updateAttributes('orderedList', {
                                        listStyle: target.value,
                                    })
                                }
                            >
                                {[
                                    'decimal',
                                    'decimal-leading-zero',
                                    'lower-alpha',
                                    'upper-alpha',
                                    'lower-roman',
                                    'upper-roman',
                                ].map((style) => (
                                    <option key={style} value={style}>
                                        {t(style)}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    )}
                    {codeBlock && (
                        <Field
                            label={t('codeLanguage')}
                            value={activeLanguage}
                            data-active-state={activeLanguage ? 'on' : 'off'}
                            onChange={({ target }) =>
                                editor.commands.updateAttributes('codeBlock', {
                                    language: target.value || null,
                                })
                            }
                        />
                    )}
                </>
            )}
        </Popover>
    )
}

export function EditorToolbar({
    editor,
    basic,
    open,
    showOutline,
    toggleOutline,
    sourceMode,
    toggleSource,
}) {
    const t = useTranslate()
    const editable = editor.isEditable
    if (sourceMode)
        return (
            <Toolbar className="toolbar source-toolbar" aria-label={t('sourceTools')}>
                <div className="toolbar-row">
                    <span className="source-mode-label">
                        <CodeXml size={16} aria-hidden="true" /> {t('htmlSource')}
                    </span>
                    <Tool label={t('back')} icon={ArrowLeft} onClick={toggleSource}>
                        {t('back')}
                    </Tool>
                </div>
            </Toolbar>
        )
    const level = editor.getAttributes('heading').level
    const heading = level && editor.isActive('heading', { level })
    const selectedNode = editor.state.selection.node?.type.name
    const styles = basic ? {} : editor.getAttributes('textStyle')
    function styleValue(attr) {
        const value = styles[attr]
        return value && editor.isActive('textStyle', { [attr]: value }) ? value : ''
    }
    return (
        <Toolbar aria-label={t('textFormatting')} className="toolbar">
            <div className="toolbar-row">
                <ToolbarGroup aria-label={t('history')}>
                    <UndoRedoButton action="undo" editor={editor} />
                    <UndoRedoButton action="redo" editor={editor} />
                </ToolbarGroup>
                <ToolbarGroup aria-label={t('typography')}>
                    <select
                        aria-label={t('paragraphStyle')}
                        title={t('paragraphStyle')}
                        disabled={!editable}
                        data-active-state={heading ? 'on' : 'off'}
                        value={heading ? level : editor.isActive('paragraph') ? 'paragraph' : ''}
                        onChange={({ target }) =>
                            target.value === 'paragraph'
                                ? editor.chain().focus().setParagraph().run()
                                : editor
                                      .chain()
                                      .focus()
                                      .setHeading({ level: Number(target.value) })
                                      .run()
                        }
                    >
                        <option value="" hidden>
                            {t('paragraphStyle')}
                        </option>
                        <option value="paragraph">{t('paragraph')}</option>
                        {[1, 2, 3, 4, 5, 6].map((n) => (
                            <option key={n} value={n}>
                                {t('headingLevel', { level: n })}
                            </option>
                        ))}
                    </select>
                    {!basic && (
                        <>
                            <StyleSelect
                                editor={editor}
                                label={t('fontFamily')}
                                attr="fontFamily"
                                value={styleValue('fontFamily')}
                                set="setFontFamily"
                                unset="unsetFontFamily"
                                values={typefaces}
                            />
                            <StyleSelect
                                editor={editor}
                                label={t('fontSize')}
                                attr="fontSize"
                                value={styleValue('fontSize')}
                                set="setFontSize"
                                unset="unsetFontSize"
                                values={sizes}
                            />
                            <StyleSelect
                                editor={editor}
                                label={t('lineHeight')}
                                attr="lineHeight"
                                value={styleValue('lineHeight')}
                                set="setLineHeight"
                                unset="unsetLineHeight"
                                values={leading}
                            />
                        </>
                    )}
                </ToolbarGroup>
                <ToolbarGroup aria-label={t('inlineFormatting')}>
                    <Marks
                        editor={editor}
                        types={basic ? basicMarks : [...basicMarks, 'subscript', 'superscript']}
                    />
                </ToolbarGroup>
                <ToolbarGroup aria-label={t('colorAndFormatting')}>
                    {!basic && (
                        <>
                            <ColorControl
                                editor={editor}
                                label={t('textColor')}
                                value={styleValue('color')}
                                command="setColor"
                                initial="#202124"
                                icon={Baseline}
                            />
                            <ColorControl
                                editor={editor}
                                label={t('backgroundColor')}
                                value={styleValue('backgroundColor')}
                                command="setBackgroundColor"
                                initial="#ffe29a"
                                icon={PaintBucket}
                            />
                            <Tool
                                label={t('highlight')}
                                icon={Highlighter}
                                active={editor.isActive('highlight')}
                                disabled={!editable}
                                onClick={() => editor.chain().focus().toggleHighlight().run()}
                            />
                        </>
                    )}
                    <Tool
                        label={t('clearFormatting')}
                        icon={Eraser}
                        disabled={!editable}
                        onClick={() => clearFormatting(editor)}
                    />
                </ToolbarGroup>
            </div>
            <div className="toolbar-row">
                {!basic && (
                    <ToolbarGroup aria-label={t('paragraphAlignment')}>
                        {['left', 'center', 'right', 'justify'].map((align) => (
                            <TextAlignButton key={align} editor={editor} align={align} />
                        ))}
                    </ToolbarGroup>
                )}
                <ToolbarGroup aria-label={t('listsAndBlocks')}>
                    {(basic
                        ? ['bulletList', 'orderedList']
                        : ['bulletList', 'orderedList', 'taskList']
                    ).map((type) => (
                        <ListButton key={type} editor={editor} type={type} />
                    ))}
                    <Tool
                        label={t('quote')}
                        icon={Quote}
                        active={editor.isActive('blockquote')}
                        disabled={!editable}
                        onClick={() => editor.chain().focus().toggleBlockquote().run()}
                    />
                    {!basic && <MoreBlocks editor={editor} />}
                </ToolbarGroup>
                <ToolbarGroup aria-label={t('insertContent')}>
                    <LinkPopover editor={editor} />
                    {!basic && (
                        <>
                            <TablePicker editor={editor} />
                            {Object.entries(mediaIcons).map(([kind, icon]) => (
                                <Tool
                                    key={kind}
                                    label={t(kind)}
                                    icon={icon}
                                    data-active-state={selectedNode === kind ? 'on' : 'off'}
                                    aria-haspopup="dialog"
                                    disabled={!editable}
                                    onClick={() => open({ kind, mode: 'insert' })}
                                />
                            ))}
                            <Tool
                                label={t('embed')}
                                icon={AppWindow}
                                data-active-state={selectedNode === 'iframe' ? 'on' : 'off'}
                                disabled={!editable}
                                aria-haspopup="dialog"
                                onClick={() => open('embed')}
                            />
                        </>
                    )}
                </ToolbarGroup>
                <ToolbarGroup aria-label={t('documentTools')} className="document-tools">
                    {!basic && (
                        <>
                            <Popover
                                label={t('findAndReplace')}
                                icon={Search}
                                active={Boolean(editor.storage.findAndReplace.searchTerm)}
                                chevron={false}
                                className="find-popover"
                            >
                                {(close) => <FindPanel editor={editor} close={() => close(true)} />}
                            </Popover>
                            <Tool
                                label={t('outline')}
                                icon={ListTree}
                                active={showOutline}
                                onClick={toggleOutline}
                            />
                        </>
                    )}
                    <Tool label={t('htmlSource')} icon={CodeXml} onClick={toggleSource} />
                </ToolbarGroup>
            </div>
        </Toolbar>
    )
}
