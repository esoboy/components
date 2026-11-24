import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { EditorContent, EditorContext, useEditor } from '@tiptap/react'
import { BubbleMenu } from '@tiptap/react/menus'
import { NodeSelection, TextSelection } from '@tiptap/pm/state'
import { closeHistory } from '@tiptap/pm/history'
import { isInTable, selectionCell } from '@tiptap/pm/tables'
import { ListTree } from 'lucide-react'
import { extensions } from './extensions'
import { applySource, serializer, setContent, sanitizeHTML, sourceText } from './content'
import { mediaBatchKind, requestMedia } from './media'
import { TranslationContext, translator, useTranslate } from './i18n'
import { handleMediaPaste } from './paste'
import { EditorToolbar, Marks } from './toolbar'
import { MediaDialog, MediaTools, mediaIcons } from './media-tools'
import { TableTools } from './table'
import { LinkPopover } from './text-tools'
import { EmbedDialog, EmbedTools } from './iframe'
import { Toolbar, ToolbarGroup } from './tiptap-ui/primitives'
import { editor as editorControl } from '../behaviors/editor.js'
import './style.css'

const bubbleOptions = {
    placement: 'top',
    strategy: 'fixed',
    offset: 8,
    flip: { padding: 8 },
    shift: { padding: 8 },
}

function EditorApp({ host, container }) {
    const t = useTranslate()
    const [panel, setPanel] = useState(null),
        [error, setError] = useState('')
    const [pending, setPending] = useState(0)
    const [source, setSource] = useState(null)
    const [outline, setOutline] = useState([]),
        [showOutline, setShowOutline] = useState(false)
    const [zoom, setZoom] = useState('100'),
        [, repaint] = useState(0)
    const frame = useRef(0)
    const basic = host.getAttribute('toolbar-preset') === 'basic'
    const select = useCallback(
        (kind, options) => {
            setPanel(null)
            setError('')
            setPending((count) => count + 1)
            requestMedia(host, kind, options)
                .catch((reason) => setError(reason.message))
                .finally(() => setPending((count) => count - 1))
        },
        [host],
    )
    const capabilities = useMemo(
        () =>
            extensions(
                container,
                setOutline,
                (files, position) =>
                    select(mediaBatchKind(files), { files, source: 'drop', position }),
                (key, values) => host.getMessage(key, values),
            ),
        [container, select, host],
    )
    const editor = useEditor({
        content: '',
        injectCSS: false,
        shouldRerenderOnTransaction: false,
        extensions: capabilities,
        editorProps: {
            transformPastedHTML: sanitizeHTML,
            handleDOMEvents: {
                paste(view, event) {
                    return handleMediaPaste(
                        view,
                        event,
                        host.getAttribute('upload-pasted-images') !== 'false',
                        select,
                    )
                },
                mousedown(view, event) {
                    // Restore editor focus before the browser places the caret. Otherwise a
                    // toolbar-to-canvas click can restore the previous selection.
                    if (
                        view.editable &&
                        event.button === 0 &&
                        host.contains(view.root.activeElement) &&
                        view.root.activeElement?.closest('.tiptap-toolbar')
                    )
                        view.focus()
                    return false
                },
                click(view, event) {
                    // Selecting a movie or attachment must keep editing focus. Native player controls
                    // still handle playback, while a click on the picture selects the video node.
                    if (view.editable && event.target.closest?.('video, a[data-type="file"]'))
                        event.preventDefault()
                    return false
                },
            },
            attributes: () => ({
                class: 'ui-rich-content document',
                role: 'textbox',
                'aria-multiline': 'true',
                'aria-label': host.getAttribute('aria-label') ?? host.getMessage('editContent'),
            }),
            handleClickOn(view, _, node, nodePos, event, direct) {
                if (!direct || !mediaIcons[node.type.name] || !view.editable) return false
                if (node.type.name === 'file') event.preventDefault()
                view.dispatch(
                    view.state.tr.setSelection(NodeSelection.create(view.state.doc, nodePos)),
                )
                view.focus()
                return true
            },
            handleKeyDown(_, event) {
                if (event.altKey && event.key === 'F10') {
                    const toolbar =
                        container.querySelector('.context-bar [role="toolbar"]') ??
                        container.querySelector('.toolbar')
                    toolbar?.querySelector('[tabindex="0"]')?.focus()
                    return true
                }
                return false
            },
        },
        onCreate({ editor: instance }) {
            host.editor = instance
            host.serialize = serializer(instance)
            host.replace(host._value, host._sourceType ?? host.getAttribute('input-type') ?? 'auto')
            host.syncState()
            host.dispatchEvent(
                new CustomEvent('editor-ready', {
                    bubbles: true,
                    composed: true,
                    detail: { value: host.value },
                }),
            )
            repaint((n) => n + 1)
        },
        onUpdate() {
            host._value = host.value
            host.dispatchEvent(
                new CustomEvent('editor-change', {
                    bubbles: true,
                    composed: true,
                    detail: { value: host._value },
                }),
            )
        },
        onTransaction() {
            if (!frame.current)
                frame.current = requestAnimationFrame(() => {
                    frame.current = 0
                    repaint((n) => n + 1)
                })
        },
        onDestroy() {
            cancelAnimationFrame(frame.current)
            host.editor = null
        },
    })
    useEffect(() => {
        const notify = (event) => setError(event.detail.message)
        const refresh = () => {
            setPanel(null)
            setError('')
            repaint((n) => n + 1)
        }
        const replaced = () => {
            refresh()
            setSource((draft) => (draft === null ? null : sourceText(host.editor)))
        }
        // Reset also discards unfinished markup that never changed the public form value.
        const form = host.closest('form')
        const resetDraft = (event) =>
            queueMicrotask(() => {
                if (!event.defaultPrevented && host.editor) replaced()
            })
        form?.addEventListener('reset', resetDraft)
        host.addEventListener('editor-error', notify)
        host.addEventListener('editor-state', refresh)
        host.addEventListener('editor-document-replaced', replaced)
        return () => {
            form?.removeEventListener('reset', resetDraft)
            host.removeEventListener('editor-error', notify)
            host.removeEventListener('editor-state', refresh)
            host.removeEventListener('editor-document-replaced', replaced)
        }
    }, [host])
    const showBubble = useCallback(
        ({ editor, state, view }) => {
            const focused = view.root.activeElement
            const videoFocused = focused?.localName === 'video' && view.dom.contains(focused)
            if (
                !editor.isEditable ||
                (!view.hasFocus() &&
                    !videoFocused &&
                    !(host.contains(focused) && focused?.closest('.context-bar')))
            )
                return false
            if (state.selection instanceof NodeSelection)
                return (
                    !basic &&
                    (state.selection.node.type.name === 'iframe' ||
                        Boolean(mediaIcons[state.selection.node.type.name]))
                )
            return (
                (!basic && isInTable(state)) ||
                (state.selection instanceof TextSelection &&
                    !state.selection.empty &&
                    !editor.isActive('codeBlock'))
            )
        },
        [basic],
    )
    const reference = useCallback(() => {
        if (!editor) return null
        const { state, view } = editor
        if (state.selection instanceof NodeSelection) {
            const dom = view.nodeDOM(state.selection.from)
            return dom?.querySelector('[data-resize-wrapper]') ?? dom
        }
        return isInTable(state) ? view.nodeDOM(selectionCell(state).pos) : null
    }, [editor])
    if (!editor) return <p role="status">{t('loading')}</p>
    const characters = editor.storage.characterCount.characters()
    const words = editor.storage.characterCount.words()
    const open = (value) => {
        setError('')
        setPanel(value)
    }
    const close = () => {
        setPanel(null)
        editor.commands.focus()
    }
    const kind = editor.state.selection.node?.type.name
    const media = !basic && Boolean(mediaIcons[kind])
    const table = !basic && !media && isInTable(editor.state)
    const embed = kind === 'iframe' && !basic
    const sourceMode = source !== null
    function toggleSource() {
        host.resetMedia()
        setPanel(null)
        setError('')
        editor.view.dispatch(closeHistory(editor.state.tr))
        if (sourceMode) {
            setSource(null)
            requestAnimationFrame(() => {
                if (!editor.isDestroyed) editor.commands.focus()
            })
        } else {
            setSource(sourceText(editor))
        }
    }
    function changeSource(value) {
        if (!editor.isEditable) return
        setSource(value)
        try {
            applySource(editor, value, t)
            setError('')
        } catch (reason) {
            setSource(source)
            setError(reason instanceof RangeError ? t('sourceTooLong') : reason.message)
        }
    }
    return (
        <EditorContext.Provider value={{ editor }}>
            <EditorToolbar
                editor={editor}
                basic={basic}
                open={open}
                sourceMode={sourceMode}
                toggleSource={toggleSource}
                showOutline={showOutline}
                toggleOutline={() => setShowOutline(!showOutline)}
            />
            {error && (
                <p className="error" role="alert">
                    {error}
                </p>
            )}
            <div className="workspace">
                {!sourceMode && !basic && showOutline && (
                    <nav className="editor-outline" aria-label={t('documentOutline')}>
                        <strong>
                            <ListTree size={15} />
                            {t('documentOutline')}
                        </strong>
                        {outline.length ? (
                            outline.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    style={{
                                        paddingInlineStart: `${Math.min(item.level, 6) * 8}px`,
                                    }}
                                    onClick={() => {
                                        editor.commands.setTextSelection(item.pos)
                                        item.dom?.scrollIntoView({ block: 'nearest' })
                                        editor.commands.focus()
                                    }}
                                >
                                    {item.textContent || t('untitled')}
                                </button>
                            ))
                        ) : (
                            <p>{t('emptyOutline')}</p>
                        )}
                    </nav>
                )}
                <div
                    className="canvas"
                    hidden={sourceMode}
                    style={{
                        '--editor-zoom': Number(zoom) / 100,
                        '--ui-file-label': JSON.stringify(t('file')),
                    }}
                >
                    <EditorContent editor={editor} />
                </div>
                {sourceMode && (
                    <textarea
                        className="source-input"
                        aria-label={t('htmlSource')}
                        spellCheck={false}
                        autoCapitalize="off"
                        autoCorrect="off"
                        autoFocus
                        readOnly={!editor.isEditable}
                        disabled={host.hasAttribute('disabled')}
                        style={{ '--editor-zoom': Number(zoom) / 100 }}
                        value={source}
                        onChange={(event) => changeSource(event.target.value)}
                    />
                )}
            </div>
            {!sourceMode && editor.isEditable && (
                <BubbleMenu
                    editor={editor}
                    pluginKey="editorContextTools"
                    appendTo={container}
                    updateDelay={0}
                    options={bubbleOptions}
                    shouldShow={showBubble}
                    getReferencedVirtualElement={reference}
                    className="context-bar"
                >
                    <Toolbar
                        variant="floating"
                        aria-label={
                            media
                                ? t('mediaActions')
                                : embed
                                  ? t('embedActions')
                                  : table
                                    ? t('tableActions')
                                    : t('selectionFormatting')
                        }
                    >
                        {media ? (
                            <MediaTools editor={editor} open={open} />
                        ) : embed ? (
                            <ToolbarGroup>
                                <EmbedTools editor={editor} open={open} />
                            </ToolbarGroup>
                        ) : table ? (
                            <TableTools editor={editor} />
                        ) : (
                            <ToolbarGroup>
                                <Marks
                                    editor={editor}
                                    types={['bold', 'italic', 'underline', 'strike']}
                                />
                                <LinkPopover editor={editor} label={t('selectionLink')} />
                            </ToolbarGroup>
                        )}
                    </Toolbar>
                </BubbleMenu>
            )}
            <footer>
                {pending ? (
                    <span role="status">{t('processingMedia')}</span>
                ) : (
                    <span role="status" aria-live="off">
                        {t(characters === 1 ? 'oneCharacter' : 'characters', { count: characters })}{' '}
                        · {t(words === 1 ? 'oneWord' : 'words', { count: words })}
                        {!editor.isEditable && <> · {t('readOnly')}</>}
                    </span>
                )}
                <select
                    aria-label={t('zoom')}
                    value={zoom}
                    onChange={(event) => setZoom(event.target.value)}
                >
                    {['75', '90', '100', '125', '150'].map((value) => (
                        <option key={value} value={value}>
                            {value}%
                        </option>
                    ))}
                </select>
            </footer>
            {(panel === 'embed' || panel === 'editEmbed') && (
                <EmbedDialog editor={editor} edit={panel === 'editEmbed'} close={close} />
            )}
            {panel?.kind && (
                <MediaDialog
                    editor={editor}
                    {...panel}
                    close={close}
                    selectLocal={(kind, replace) => select(kind, { replace })}
                />
            )}
        </EditorContext.Provider>
    )
}

class EsoboyEditor extends HTMLElement {
    static observedAttributes = [
        'readonly',
        'disabled',
        'output-type',
        'toolbar-preset',
        'aria-label',
        'lang',
        'data-messages',
    ]
    constructor() {
        super()
        this._value = ''
        this.getMessage = translator('')
        this.editor = null
        this.mediaSession = new AbortController()
    }
    connectedCallback() {
        if (this.root) return
        const container = document.createElement('div')
        container.className = 'editor'
        container.setAttribute('x-ignore', '')
        // Submitting an editor dialog must not submit the surrounding application form.
        container.addEventListener('submit', (event) => event.stopPropagation())
        this.replaceChildren(container)
        this.root = createRoot(container)
        this.container = container
        this.refreshLanguage()
    }
    refreshLanguage() {
        const locale = this.closest('[lang]')?.lang ?? ''
        const overrides = this.dataset.messages
        this.getMessage = translator(locale, overrides && JSON.parse(overrides))
        this.root.render(
            <TranslationContext.Provider value={this.getMessage}>
                <EditorApp host={this} container={this.container} />
            </TranslationContext.Provider>,
        )
        // Refresh engine-owned labels while retaining the document, selection and undo history.
        this.editor?.createNodeViews()
        this.container
            .querySelector('.drag-handle')
            ?.setAttribute('aria-label', this.getMessage('dragBlock'))
    }
    disconnectedCallback() {
        queueMicrotask(() => {
            if (this.isConnected) return
            this.resetMedia()
            this._value = this.value
            this._sourceType = this.getAttribute('output-type') ?? 'html'
            this.root?.unmount()
            this.root = null
        })
    }
    attributeChangedCallback(name, previous, value) {
        if (previous === value || !this.root) return
        if (name === 'lang' || name === 'data-messages') this.refreshLanguage()
        else this.syncState()
    }
    resetMedia() {
        this.mediaSession.abort()
        this.mediaSession = new AbortController()
    }
    syncState() {
        if (!this.editor) return
        const disabled = this.hasAttribute('disabled'),
            readonly = this.hasAttribute('readonly')
        if (disabled || readonly) this.resetMedia()
        this.editor.setEditable(!disabled && !readonly, false)
        this.editor.view.dom.setAttribute('aria-readonly', String(disabled || readonly))
        this.editor.view.dom.setAttribute('aria-disabled', String(disabled))
        this.dispatchEvent(new CustomEvent('editor-state'))
    }
    get value() {
        return this.editor
            ? this.serialize(this.getAttribute('output-type') ?? 'html')
            : this._value
    }
    set value(value) {
        this.replace(value)
    }
    replace(value, type = this.getAttribute('input-type') ?? 'auto') {
        if (typeof value !== 'string') throw new TypeError(this.getMessage('contentTypeError'))
        this._value = value
        this._sourceType = type
        if (this.editor) {
            try {
                this.resetMedia()
                setContent(this.editor, value, type, this.getMessage)
                this.dispatchEvent(new CustomEvent('editor-document-replaced'))
            } catch (error) {
                this.dispatchEvent(
                    new CustomEvent('editor-error', {
                        detail: { message: error.message },
                        bubbles: true,
                        composed: true,
                    }),
                )
                throw error
            }
        }
    }
    focus(options) {
        const source = this.querySelector('.source-input')
        if (source) source.focus(options)
        else if (this.editor) this.editor.commands.focus()
        else super.focus(options)
    }
    getHTML() {
        return this.editor?.getHTML() ?? ''
    }
    getMarkdown() {
        return this.editor ? this.serialize('markdown') : ''
    }
}

if (!customElements.get('esoboy-editor')) {
    const register = () => window.Alpine.data('uiEditor', editorControl)
    if (window.Alpine) register()
    else document.addEventListener('alpine:init', register, { once: true })
    customElements.define('esoboy-editor', EsoboyEditor)
}
