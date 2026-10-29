/*
 * <eso-editor>
 *
 * A Tiptap editor on the HTML its view rendered, sanitized, into [data-slot="editor-content"],
 * writing what data-format allows and offering the tools it lists (its features, palette,
 * fonts, …, from the server). .value is the HTML, '' when empty: input comes from the
 * element on every change and change when it loses focus, so wire:model binds on it, and
 * data-name keeps a hidden input for forms.
 *
 * Controls: [data-editor-command] (and data-editor-value) runs a command and shows whether
 * it is on (aria-pressed, aria-checked in menus) or possible; [data-editor-action] opens
 * what the editor has besides (media, find, source, full screen). The toolbar is one line
 * and one tab stop, arrow keys moving within it, what doesn't fit in More; one tooltip
 * names whichever control is pointed at.
 *
 * Media: the editor uploads nothing. It asks with editor-media { files, accept, insert }
 * (files dropped or pasted, or none from a button: accept says of which kinds) and
 * inserts what comes back, in place, through detail.insert(attachments) or
 * Ui::editor(id)->insert(): an array of { url, name, mime, size, alt, width, height,
 * poster }, or none for nothing chosen. Images it has no copy of (external, data:) are
 * the app's to save when someone asks (images.js).
 *
 * Built into dist/editor.js; the view loads it through @assets.
 */

import { Editor, createDocument } from '@tiptap/core'
import BubbleMenu from '@tiptap/extension-bubble-menu'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import Typography from '@tiptap/extension-typography'
import { CharacterCount, Placeholder } from '@tiptap/extensions'
import { closeHistory } from '@tiptap/pm/history'
import { Slice } from '@tiptap/pm/model'
import { NodeSelection, TextSelection } from '@tiptap/pm/state'
import { CellSelection } from '@tiptap/pm/tables'
import StarterKit from '@tiptap/starter-kit'
import { marked } from 'marked'
import { define, writeHidden } from '../support/element.js'
import { clone, readTemplates } from '../support/templates.js'
import { COMMANDS } from './editor/commands.js'
import { MoveBlocks, dragHandle } from './editor/drag.js'
import { Find, findKey } from './editor/find.js'
import { Saving, dataFile, rotateImage, savingKey, unsaved, unsavedImages, wordImages } from './editor/images.js'
import { accepted, attachmentNode, safeUrl, videoNode } from './editor/media.js'
import { Audio, PlainCodeBlock, Video, callout, collapsible, embed, figures, file, formulas, picture, tables, tasks, toMathML } from './editor/nodes.js'
import { wordLists } from './editor/paste.js'
import { blockStyle, computed, restyle, styleExtensions } from './editor/styles.js'
import { emojiMenu, slashMenu } from './editor/suggest.js'
import { overflow } from './editor/toolbar.js'
import { uploads, uploadsKey } from './editor/uploads.js'
import { generic, passthrough, whitelist } from './editor/whitelist.js'

const MAC = /Mac|iPhone|iPad/.test(navigator.platform ?? '')
const MATH = ['inlineMath', 'blockMath']

// The nodes and marks that keep their whitelisted style (and other attributes), and the
// marks that keep only other attributes.
const STYLED = ['paragraph', 'heading', 'blockquote', 'bulletList', 'orderedList', 'listItem', 'taskList', 'taskItem', 'codeBlock', 'horizontalRule', 'details', 'detailsSummary', 'detailsContent', 'table', 'tableRow', 'tableCell', 'tableHeader', 'image', 'video', 'audio', 'file', 'embed', 'figure', 'figcaption', 'callout', 'textStyle', 'highlight', 'link']
const MARKS = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript']
const ROVE = ['ArrowLeft', 'ArrowRight', 'Home', 'End']

// What arrow keys move to in More (its rows) and in a grid of swatches.
const STEPS = { ArrowLeft: [0, -1], ArrowRight: [0, 1], ArrowUp: [-1, 0], ArrowDown: [1, 0], Home: [0, -Infinity], End: [0, Infinity] }

// An auto popover is open: Escape is its, to close it.
const popoverOpen = () => !! document.querySelector('[popover]:not([popover="manual"]):popover-open')

// Text that reads as Markdown: headings, lists, quotes, fences, tables, emphasis, links.
const MARKDOWN = /^(#{1,6}\s|\s*[-*+]\s|\s*\d+[.)]\s|>\s|```|\|.*\|\s*$)|\*\*\S[^*]*\*\*|\[[^\]]+\]\([^)\s]+\)/m

// One grapheme per character, so 汉字 and 👍🏽 count one each.
const segmenter = typeof Intl.Segmenter === 'function' ? new Intl.Segmenter() : null
const characters = text => segmenter ? [...segmenter.segment(text)].length : [...text].length

// Mod-B: ⌘B on Apple systems, Ctrl+B elsewhere; and as aria-keyshortcuts.
const keys = shortcut => shortcut.split('-').map(key => key === 'Mod' ? (MAC ? '⌘' : 'Ctrl') : key === 'Shift' ? (MAC ? '⇧' : 'Shift') : key === 'Alt' ? (MAC ? '⌥' : 'Alt') : key.toUpperCase()).join(MAC ? '' : '+')
const ariaKeys = shortcut => shortcut.split('-').map(key => key === 'Mod' ? (MAC ? 'Meta' : 'Control') : key.length === 1 ? key.toUpperCase() : key).join('+')

// The HTML a block to a line, for the source view.
const lines = html => html.replace(/(<\/(?:p|h[1-6]|li|ul|ol|blockquote|pre|table|tr|thead|tbody|colgroup|details|summary|div|iframe|video|audio)>|<(?:hr|br|col|img)[^>]*>|<(?:ul|ol|table|tbody|tr|details)[^>]*>)(?!\n)/g, '$1\n').trim()

const focused = (view, element) => view.hasFocus() || element.contains(document.activeElement)

// Touch screens show their own menu over a selection: the bubbles go under it there.
const TOUCH = typeof matchMedia === 'function' && matchMedia('(pointer: coarse)').matches

const MEDIA = ['image', 'video', 'audio', 'file']

export class EditorElement extends HTMLElement {
    static observedAttributes = ['aria-invalid', 'aria-describedby', 'data-disabled']

    #editor = null
    #focused = null
    #reported = ''
    #format = null
    #whitelist = null
    #saves = []
    #saved = new Map()
    #turns = new Map()
    #unsaved = { doc: null, images: [] }
    #hosts = []
    #rtf = ''
    #painter = null
    #toolbar = null
    #requests = new Set()
    #tracked = new Set()
    #selected = null
    #anchor = null
    #context = ''
    #menus = []
    #mathPreview = 0

    connectedCallback() {
        if (this.#editor) {
            window.addEventListener('ui-editor', this.#server)

            return
        }

        const content = this.querySelector(':scope > [data-slot="editor-content"]')

        if (! content) return

        this.#format = { features: [], headings: [1, 2, 3, 4, 5, 6], colors: [], highlights: [], fonts: [], sizes: [], lineHeights: [], indents: 8, platforms: [], styles: {}, ...JSON.parse(this.dataset.format || '{}') }
        this.#whitelist = whitelist(this.#format)
        this.#hosts = (this.dataset.imageHosts ?? '').split(/\s+/).filter(Boolean)

        const html = content.innerHTML
        const labels = JSON.parse(this.dataset.labels || '{}')

        content.replaceChildren()

        this.#editor = new Editor({
            element: content,
            content: html,
            editable: ! this.hasAttribute('data-disabled'),
            extensions: this.#extensions(),
            editorProps: {
                attributes: () => ({
                    'data-slot': 'editor-input',
                    'role': 'textbox',
                    'aria-multiline': 'true',
                    'aria-label': this.dataset.label ?? '',
                    // How to reach the toolbar and the bubble menus from the text.
                    'aria-description': labels.keyboard ?? '',
                    ...this.dataset.placeholder ? { 'aria-placeholder': this.dataset.placeholder } : {},
                    ...this.hasAttribute('data-disabled') ? { 'aria-disabled': 'true' } : {},
                    ...this.#aria(),
                }),
                // Word's pictures come in its RTF, read as the paste or drop begins.
                transformPastedHTML: html => this.#whitelist.restrict(wordLists(this.#has('image') ? wordImages(html, this.#rtf) : html)),
                handlePaste: (view, event, slice) => this.#paste(view, event, slice),
                handleDrop: (view, event, slice, moved) => this.#drop(view, event, moved),
                handleDOMEvents: {
                    paste: (view, event) => this.#readRtf(event.clipboardData),
                    drop: (view, event) => this.#readRtf(event.dataTransfer),
                    pointerup: () => this.#paint(),
                    keyup: () => this.#paint(),
                },
            },
            onUpdate: () => {
                const value = this.value

                writeHidden(this, this.dataset.name, value)

                // A change that leaves the value as it was (the paragraph kept after a last
                // table or image) isn't one.
                if (value === this.#reported) return

                this.#reported = value
                this.dispatchEvent(new Event('input', { bubbles: true }))
            },
            onFocus: () => {
                this.#focused ??= this.value
            },
            onBlur: ({ event }) => {
                // Into the toolbar, a menu or a form of the editor's is still editing.
                if (event?.relatedTarget && this.contains(event.relatedTarget)) return
                this.#changed()
            },
            onTransaction: ({ transaction }) => {
                for (const request of [...this.#requests, ...this.#tracked]) {
                    request.from = transaction.mapping.map(request.from)
                    request.to = transaction.mapping.map(request.to)
                }

                this.#sync()
            },
        })

        // What keeps the document in shape (a paragraph after a last table or image) is
        // done now, not on the first change: on phones Tiptap focuses the editor in the
        // middle of a command, and the document changing then would fail the command.
        this.#editor.view.dispatch(this.#editor.state.tr.setMeta('addToHistory', false).setMeta('preventUpdate', true))

        this.#reported = this.value
        this.#listen()
        this.#prepareControls(this)

        const toolbar = this.#part('editor-toolbar')

        if (toolbar && typeof ResizeObserver === 'function') this.#toolbar = overflow(toolbar, () => this.#rovingStart())

        this.#sync()
    }

    disconnectedCallback() {
        window.removeEventListener('ui-editor', this.#server)

        // Moved, it comes back at once; removed, its editor goes.
        queueMicrotask(() => {
            if (this.isConnected) return

            this.#fullscreen(false)
            this.#toolbar?.disconnect()
            this.#editor?.destroy()
            this.#editor = null
        })
    }

    attributeChangedCallback(name) {
        if (! this.#editor) return

        if (name === 'data-disabled') this.#editor.setEditable(! this.hasAttribute('data-disabled'))

        // The text box carries the field's error state.
        this.#editor.view.setProps({})
        this.#sync()
    }

    get editor() {
        return this.#editor
    }

    // The HTML, without the empty paragraph kept last to write after a table or image.
    get value() {
        if (! this.#editor) return this.querySelector('[data-slot="editor-content"]')?.innerHTML.trim() ?? ''

        return this.#editor.isEmpty ? '' : this.#editor.getHTML().replace(/<p><\/p>$/, '')
    }

    // From the server (wire:model): its HTML replaces ours, without reporting back, the
    // cursor staying where it was. The same content written differently (the server's
    // sanitized copy) leaves it be.
    set value(value) {
        value ??= ''

        if (! this.#editor || value === this.value || this.#holds(value)) return

        const { from, to } = this.#editor.state.selection
        const near = (doc, position) => doc.resolve(Math.min(position, doc.content.size))

        this.#editor.chain()
            .setContent(value, { emitUpdate: false })
            .command(({ tr }) => !! tr.setSelection(TextSelection.between(near(tr.doc, from), near(tr.doc, to))))
            .run()
        this.#reported = this.value
        writeHidden(this, this.dataset.name, this.value)
    }

    // Whether the editor has this content, give or take its last empty paragraph.
    #holds(html) {
        const incoming = createDocument(html, this.#editor.schema)
        const { doc } = this.#editor.state
        const last = doc.lastChild

        if (incoming.eq(doc)) return true

        return doc.childCount > 1 && last.type.name === 'paragraph' && ! last.content.size && incoming.eq(doc.copy(doc.content.cut(0, doc.content.size - last.nodeSize)))
    }

    /**
     * Inserts attachments where the cursor is, as an answer to editor-media does where it
     * was asked: images, audio and video as themselves, the rest as files.
     */
    insert(attachments) {
        this.#insert(attachments, null)
    }

    // A feature of the format: what can be written, or a tool.
    #has(feature) {
        return this.#format.features.includes(feature)
    }

    #extensions() {
        const has = feature => this.#has(feature)
        const templates = readTemplates(this)
        const lists = has('bullet-list') || has('ordered-list')
        const max = Number(this.dataset.maxLength) || null
        const labels = { expand: 'Expand', collapse: 'Collapse', task: 'Done', caption: '', ...JSON.parse(this.dataset.labels || '{}') }
        const list = this.#whitelist
        const styles = this.#format.styles

        return [
            // What the whitelist keeps of every node and mark, and of the tags no feature
            // writes.
            passthrough(list, STYLED, MARKS),
            generic(list),
            // A paragraph is kept after a last table or image, to write after it; the value
            // leaves it out.
            StarterKit.configure({
                heading: has('heading') && { levels: this.#format.headings },
                bold: has('bold') && {},
                italic: has('italic') && {},
                underline: has('underline') && {},
                strike: has('strike') && {},
                code: has('code') && {},
                link: has('link') && { openOnClick: false, autolink: true, defaultProtocol: 'https' },
                bulletList: has('bullet-list') && {},
                orderedList: has('ordered-list') && {},
                listItem: lists && {},
                listKeymap: (lists || has('task-list')) && {},
                blockquote: has('blockquote') && {},
                horizontalRule: has('horizontal-rule') && {},
                codeBlock: false,
            }),
            // The placeholder on an empty editor's paragraph, a caption's on an empty caption
            // (as the CSS shows them). Asked while the document changes, before editor.state
            // has the new one: positions in it would be out of date.
            Placeholder.configure({
                includeChildren: true,
                placeholder: ({ node }) => node.type.name === 'figcaption' ? labels.caption : this.dataset.placeholder ?? '',
            }),
            styleExtensions(this.#format, list),
            has('superscript') && Superscript,
            has('subscript') && Subscript,
            has('task-list') && tasks(labels),
            has('details') && collapsible(labels),
            has('code-block') && PlainCodeBlock,
            has('table') && tables(styles, list),
            has('image') && [picture(list), figures(styles), Saving],
            has('video') && Video,
            has('audio') && Audio,
            has('file') && file(styles.card),
            has('embed') && embed(this.#format.platforms),
            has('math') && formulas(),
            has('callout') && callout(styles),
            accepted(this.#format.features).length && templates.uploading && uploads({ uploading: templates.uploading, missing: has('image') ? templates['missing-image'] : null }),
            has('find-replace') && Find,
            (has('character-count') || max) && CharacterCount.configure({ limit: max, textCounter: characters }),
            has('typography') && Typography,
            has('drag-handle') && templates['drag-handle'] && dragHandle(this.#menu(clone(templates['drag-handle']))),
            has('drag-handle') && MoveBlocks,
            has('slash-menu') && templates.slash && slashMenu(templates.slash, option => this.#run(option)),
            has('emoji') && this.dataset.emoji && templates.emoji && emojiMenu(templates.emoji, this.dataset.emoji),
            has('bubble-menu') && this.#bubbles(templates),
        ].flat(Infinity).filter(Boolean)
    }

    // The bubble menus: for selected text, in a table, on a selected block, on a formula.
    #bubbles(templates) {
        const node = state => state.selection.node?.type.name
        const menus = {
            text: ({ editor, view, state, from, to, element }) => state.selection instanceof TextSelection && ! state.selection.empty && !! state.doc.textBetween(from, to).trim() && ! editor.isActive('codeBlock') && focused(view, element),
            table: ({ editor, view, state, element }) => editor.isActive('table') && (state.selection.empty || state.selection instanceof CellSelection) && focused(view, element),
            node: ({ view, state, element }) => state.selection instanceof NodeSelection && ! MATH.includes(node(state)) && focused(view, element),
            math: ({ view, state, element }) => MATH.includes(node(state)) && focused(view, element),
            // The cursor in a link: where it goes, to open, change or take off.
            link: ({ editor, view, state, element }) => state.selection.empty && editor.isActive('link') && focused(view, element),
        }

        return Object.entries(menus).filter(([name]) => templates[`bubble-${name}`]).map(([name, shouldShow]) => BubbleMenu.extend({ name: `bubble-${name}` }).configure({
            pluginKey: `bubble-${name}`,
            element: this.#menu(clone(templates[`bubble-${name}`])),
            updateDelay: 100,
            shouldShow: props => props.editor.isEditable && shouldShow(props),
            // The table's menu sits above the table, not the cursor; the others above the
            // selection, or under it on touch screens, clear of their own menu.
            getReferencedVirtualElement: name === 'table' ? () => this.#tableRect() : undefined,
            options: { placement: TOUCH && name !== 'table' ? 'bottom' : 'top', offset: 8, flip: {}, shift: { padding: 8 }, onShow: () => queueMicrotask(() => this.#sync(true)) },
        }))
    }

    #tableRect() {
        const { $from } = this.#editor.state.selection

        for (let depth = $from.depth; depth > 0; depth--) {
            if ($from.node(depth).type.name === 'table') {
                const dom = this.#editor.view.nodeDOM($from.before(depth))

                return dom instanceof Element ? { getBoundingClientRect: () => dom.getBoundingClientRect(), getClientRects: () => [dom.getBoundingClientRect()] } : null
            }
        }

        return null
    }

    // Tooltips and shortcuts for the controls in an element (a menu cloned later, too);
    // menu items say what they are themselves.
    #prepareControls(root) {
        const tip = this.querySelector(':scope > [data-editor-tip]')

        for (const control of root?.querySelectorAll?.('button[aria-label]') ?? []) {
            if (control.closest('[popover]')) continue
            if (tip) control.dataset.tooltip = tip.id
            if (control.dataset.editorKeys) control.setAttribute('aria-keyshortcuts', ariaKeys(control.dataset.editorKeys))
        }

        return root
    }

    // A bubble menu, kept to update while it is out of the page.
    #menu(element) {
        this.#menus.push(this.#prepareControls(element))

        return element
    }

    // The elements matching, in the editor and its menus wherever they are.
    #all(selector) {
        return [...new Set([...this.querySelectorAll(selector), ...this.#menus.flatMap(menu => [...menu.matches(selector) ? [menu] : [], ...menu.querySelectorAll(selector)])])]
    }

    #listen() {
        this.addEventListener('click', event => {
            const control = event.target.closest?.('[data-editor-command], [data-editor-action]')

            if (control && ! control.disabled && this.contains(control)) this.#run(control)
        })

        this.addEventListener('keydown', event => this.#key(event))
        this.addEventListener('input', event => this.#typed(event))
        this.addEventListener('change', event => {
            if (event.target.matches?.('[data-editor-alt-input]')) this.#setAlt(event.target.value)
        })
        this.addEventListener('toggle', event => this.#opened(event), true)
        this.addEventListener('emoji-select', event => {
            this.#editor.chain().focus().insertContent(event.detail.emoji).run()
        })

        // The tooltip follows the control pointed at or focused.
        this.addEventListener('pointerover', event => this.#tip(event.target.closest?.('[data-tooltip]')))
        this.addEventListener('focusin', event => {
            this.#tip(event.target.closest?.('[data-tooltip]'))
            if (! this.#editor.view.dom.contains(event.target)) this.#focused ??= this.value
        })
        this.addEventListener('focusout', event => {
            if (! this.contains(event.relatedTarget)) this.#changed()
        })

        window.addEventListener('ui-editor', this.#server)
    }

    // change once editing ends, if it changed anything.
    #changed() {
        if (this.#focused !== null && this.#focused !== this.value) this.dispatchEvent(new Event('change', { bubbles: true }))
        this.#focused = null
    }

    #server = event => {
        const { id, action, attachments, html } = event.detail ?? {}

        if (! this.#editor || ! id || id !== this.id) return

        if (action === 'insert') this.#insert(attachments, [...this.#requests].pop() ?? null)
        if (action === 'replace-images') this.#replaceImages(attachments, this.#saves.findLast(request => request.kind === 'external'))
        if (action === 'replace-data-images') this.#replaceImages(attachments, this.#saves.findLast(request => request.kind === 'data'))
        if (action === 'insert-content' && html) this.#editor.chain().focus().insertContent(this.#whitelist.restrict(html)).run()
        if (action === 'focus') this.#editor.commands.focus()
    }

    // What a control does.
    #run(control) {
        const { editorCommand: command, editorValue: value = '', editorAction: action } = control.dataset
        const editor = this.#editor

        // A swatch or an item picked in a popover closes it, and More around it.
        const done = () => {
            control.closest?.('[popover]')?.hidePopover()
            control.closest?.('[data-editor-more-content]')?.hidePopover()
        }

        if (command) {
            this.#can(command, value, editor.chain().focus())
            done()

            return
        }

        if (['media', 'find', 'source', 'fullscreen', 'format-painter'].includes(action)) done()

        // Media of the button's kind, or the selected one's to replace it.
        if (action === 'media') this.#request([], null, value ? [value] : MEDIA.includes(editor.state.selection.node?.type.name) ? [editor.state.selection.node.type.name] : null)
        if (action === 'video-apply') this.#video(control.closest('[popover]'))
        if (action === 'rotate-left') this.#rotate(-90)
        if (action === 'rotate-right') this.#rotate(90)
        if (action === 'save-image') this.#save([editor.state.selection.node?.attrs.src].filter(Boolean))
        if (action === 'save-images') this.#save(this.#unsavedImages())
        if (action === 'find') this.#find(this.#part('editor-find')?.hidden ?? false)
        if (action === 'source') this.#source(! this.hasAttribute('data-source'))
        if (action === 'fullscreen') this.#fullscreen(! this.hasAttribute('data-fullscreen'))
        if (action === 'link-apply') this.#link(control.closest('[popover]'), control.closest('[popover]')?.querySelector('[data-editor-link-input]')?.value ?? '')
        if (action === 'link-remove') this.#link(control.closest('[popover]'), '')
        if (action === 'math-apply') this.#math(control.closest('[data-editor-math-form]'))
        if (action === 'find-next') editor.commands.findNext(1)
        if (action === 'find-previous') editor.commands.findNext(-1)
        if (action === 'find-case') this.#search(control.getAttribute('aria-pressed') !== 'true')
        if (action === 'replace') editor.chain().replace(this.#field('editor-replace-input')).findNext(0).run()
        if (action === 'replace-all') editor.commands.replaceAll(this.#field('editor-replace-input'))
        if (action === 'find-close') this.#find(false)
        if (action === 'format-painter') this.#pick(! this.#painter)
        if (action === 'dismiss-upload') editor.commands.removeUpload(value)
        if (action === 'upload-missing') this.#uploadMissing(value)
        if (action === 'image-link-apply') this.#imageLink(control.closest('[popover]'), control.closest('[popover]')?.querySelector('[data-editor-image-link-input]')?.value ?? '')
        if (action === 'image-link-remove') this.#imageLink(control.closest('[popover]'), '')

        // A control in a menu or a popover opens one elsewhere: the toolbar's, More first
        // when it is in there.
        if (action === 'open') {
            const trigger = this.querySelector(`[data-slot="editor-toolbar"] [data-editor-opens="${control.dataset.editorValue}"]`)
            const more = trigger?.closest('[data-editor-more-content]')

            if (more && ! more.matches(':popover-open')) more.showPopover()
            trigger?.click()
        }
    }

    #part(slot) {
        return this.querySelector(`:scope > [data-slot="${slot}"]`)
    }

    #field(name) {
        return this.querySelector(`[data-${name}]`)?.value ?? ''
    }

    #key(event) {
        const target = event.target

        // Enter in an editor's field applies it, rather than submitting a form around the editor.
        if (event.key === 'Enter' && ! event.shiftKey && target.matches?.('[data-editor-link-input]')) {
            event.preventDefault()
            this.#link(target.closest('[popover]'), target.value)
        }

        if (event.key === 'Enter' && target.matches?.('[data-editor-video-input]')) {
            event.preventDefault()
            this.#video(target.closest('[popover]'))
        }

        if (event.key === 'Enter' && target.matches?.('[data-editor-alt-input]')) {
            event.preventDefault()
            this.#setAlt(target.value)
            this.#editor.commands.focus()
        }

        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey) && target.matches?.('[data-editor-math-input]')) {
            event.preventDefault()
            this.#math(target.closest('[data-editor-math-form]'))
        }

        if (target.matches?.('[data-editor-find-input], [data-editor-replace-input]')) {
            if (event.key === 'Enter') {
                event.preventDefault()
                target.matches('[data-editor-find-input]') ? this.#editor.commands.findNext(event.shiftKey ? -1 : 1) : this.#run({ dataset: { editorAction: 'replace' } })
            }

            if (event.key === 'Escape') {
                event.preventDefault()
                this.#find(false)
            }
        }

        // Mod-K: the link's popover, from the toolbar or the bubble menu, whichever shows.
        if ((event.key === 'k' || event.key === 'K') && (MAC ? event.metaKey : event.ctrlKey) && this.#editor.view.dom.contains(target)) {
            const link = this.#all('[data-editor-opens="link"]').find(control => control.isConnected && ! control.disabled && (control.checkVisibility?.() ?? true))

            event.preventDefault()
            link?.click()
        }

        // Find opens over the browser's own, from the text or the bar.
        if ((event.key === 'f' || event.key === 'F') && (MAC ? event.metaKey : event.ctrlKey) && ! event.altKey && this.#part('editor-find') && this.contains(target)) {
            event.preventDefault()
            this.#find(true)
        }

        if (event.key === 'Escape' && this.#painter) {
            event.preventDefault()
            this.#pick(false)
        }

        // Alt+F10: from the text to the bubble menu beside it, else the toolbar; Escape
        // there goes back to the text, where it was.
        if (event.key === 'F10' && event.altKey && this.#editor.view.dom.contains(target)) {
            event.preventDefault()
            this.#enterBars()
        }

        if (event.key === 'Escape' && ! event.defaultPrevented && ! popoverOpen() && target.closest?.('[data-slot="editor-toolbar"], [data-slot="editor-bubble"]') && ! target.closest('[popover]')) {
            event.preventDefault()
            this.#editor.view.focus()
        }

        if (event.key === 'Escape' && this.hasAttribute('data-fullscreen') && ! event.defaultPrevented && ! popoverOpen()) {
            event.preventDefault()
            this.#fullscreen(false)
        }

        if (target.matches?.('[data-slot="editor-swatch"], [data-slot="editor-spacing-value"]') && STEPS[event.key]) this.#step(event, [...target.closest('[popover]').querySelectorAll('[role="group"]')].map(group => [...group.querySelectorAll('button')]))

        const popover = target.closest?.('[popover]')
        const bar = popover ? (popover.matches('[data-editor-more-content]') ? popover : null) : target.closest?.('[data-slot="editor-toolbar"], [data-slot="editor-bubble"]')

        if (bar?.matches('[data-editor-more-content]') && STEPS[event.key] && ! target.matches('input, textarea')) this.#step(event, [...bar.querySelectorAll('[data-slot="editor-more-group"]')].map(row => this.#controlsOf(bar).filter(control => row.contains(control))))
        else if (bar && ROVE.includes(event.key) && ! target.matches('input, textarea')) this.#rove(event, bar)
    }

    #typed(event) {
        const target = event.target

        if (target.matches?.('[data-editor-find-input]')) this.#search()
        if (target.matches?.('[data-slot="editor-source"]')) this.#editor.commands.setContent(this.#whitelist.restrict(target.value), { emitUpdate: true })
        if (target.matches?.('[data-editor-math-input], [data-editor-math-display]')) this.#previewMath(target.closest('[data-editor-math-form]'))
        if (target.matches?.('[data-editor-video-input]')) target.removeAttribute('aria-invalid')
    }

    // A popover of the editor's opened: its fields start from the selection.
    #opened(event) {
        const popover = event.target

        if (event.newState !== 'open' || ! popover.matches?.('[popover]')) return

        const editor = this.#editor
        const link = popover.querySelector('[data-editor-link-input]')
        const video = popover.querySelector('[data-editor-video-input]')
        const math = popover.querySelector('[data-editor-math-form]')
        const imageLink = popover.querySelector('[data-editor-image-link-input]')

        if (imageLink) {
            imageLink.value = editor.state.selection.node?.attrs.href ?? ''
            imageLink.select()
        }

        if (link) {
            link.value = editor.getAttributes('link').href ?? ''
            link.select()
            popover.querySelector('[data-editor-action="link-remove"]')?.toggleAttribute('hidden', ! editor.isActive('link'))
        }

        if (video) {
            video.value = ''
            video.removeAttribute('aria-invalid')
        }

        if (math) this.#fillMath(math, '', false)

        this.#sync(true)

        // Its first field, else its current choice or first control, takes focus, so typing
        // and arrow keys work at once (menus focus themselves; More, its first control).
        if (popover.matches('[popover="manual"], [role="menu"]') || popover.contains(document.activeElement)) return

        const first = popover.matches('[data-editor-more-content]')
            ? this.#controlsOf(popover)[0]
            : popover.querySelector('input:not([type="hidden"]):not([disabled]), textarea') ?? popover.querySelector('[aria-pressed="true"]') ?? popover.querySelector('button:not([disabled])')

        first?.focus()
    }

    // Sets the selection's link to the address, or removes it for none.
    #link(popover, href) {
        const chain = this.#editor.chain().focus().extendMarkRange('link')

        href = href.trim()
        ;(href ? chain.setLink({ href }) : chain.unsetLink()).run()
        popover?.hidePopover()
    }

    // A video from its link: a file's address, a platform's share link or embed code.
    #video(popover) {
        const input = popover?.querySelector('[data-editor-video-input]')
        const node = videoNode(input?.value, this.#format.features, this.#format.platforms)

        if (! node) return input?.setAttribute('aria-invalid', 'true')

        this.#editor.chain().focus().insertContent(node).run()
        popover.hidePopover()
        popover.closest('[data-editor-more-content]')?.hidePopover()
    }

    #fillMath(form, latex, display) {
        form.querySelector('[data-editor-math-input]').value = latex
        form.querySelector('[data-editor-math-display]')?.toggleAttribute('checked', display)
        if (form.querySelector('[data-editor-math-display]')) form.querySelector('[data-editor-math-display]').checked = display
        this.#previewMath(form)
    }

    // The formula as it will show, or KaTeX's complaint.
    async #previewMath(form) {
        const latex = form.querySelector('[data-editor-math-input]').value.trim()
        const display = !! form.querySelector('[data-editor-math-display]')?.checked
        const preview = form.querySelector('[data-editor-math-preview]')
        const error = form.querySelector('[data-editor-math-error]')
        const token = ++this.#mathPreview

        let mathml = null
        let message = ''

        try {
            mathml = latex ? await toMathML(latex, display) : null
        } catch (failure) {
            message = failure.message
        }

        if (token !== this.#mathPreview) return

        if (preview) preview.innerHTML = mathml ?? ''
        if (error) {
            error.textContent = message
            error.hidden = ! message
        }
        form.querySelector('[data-editor-math-input]').toggleAttribute('aria-invalid', !! message)
    }

    // Inserts the form's formula, or replaces the selected one with it.
    async #math(form) {
        if (! form) return

        const latex = form.querySelector('[data-editor-math-input]').value.trim()
        const display = !! form.querySelector('[data-editor-math-display]')?.checked
        let mathml

        if (! latex) return

        try {
            mathml = await toMathML(latex, display)
        } catch {
            return this.#previewMath(form)
        }

        const node = { type: display ? 'blockMath' : 'inlineMath', attrs: { latex, mathml } }
        const { selection } = this.#editor.state
        const chain = this.#editor.chain().focus()

        ;(MATH.includes(selection.node?.type.name) && ! form.closest('[popover]') ? chain.insertContentAt({ from: selection.from, to: selection.to }, node) : chain.insertContent(node)).run()
        form.closest('[popover]')?.hidePopover()
        form.closest('[data-editor-more-content]')?.hidePopover()
    }

    #setAlt(alt) {
        if (this.#editor.state.selection.node?.type.name === 'image') this.#editor.commands.updateAttributes('image', { alt })
    }

    /**
     * Asks for media to insert, at the selection or where files were dropped: the answer,
     * any time later, goes where that place has moved to. accept is the kinds asked
     * for (a button's), or all the format takes. A placeholder asking (a picture that
     * couldn't be pasted) goes with the answer, staying when nothing was chosen.
     */
    #request(files = [], range = null, kinds = null, placeholder = null) {
        const accept = kinds ?? accepted(this.#format.features)

        if (! accept.length) return

        const { from, to } = range ?? this.#editor.state.selection
        const request = { from, to, id: placeholder ?? `upload-${Date.now()}-${this.#requests.size}`, keep: !! placeholder }

        this.#requests.add(request)
        if (this.#requests.size > 20) this.#requests.delete(this.#requests.values().next().value)

        // Files take a while to upload: a placeholder stands where they will go.
        if (files.length) this.#editor.commands.addUpload?.(request.id, from)

        this.dispatchEvent(new CustomEvent('editor-media', {
            bubbles: true,
            detail: { files: [...files], accept: [...accept], insert: attachments => this.#insert(attachments, request) },
        }))
    }

    #insert(attachments, request) {
        const nodes = (Array.isArray(attachments) ? attachments : [attachments]).filter(Boolean).map(attachment => attachmentNode(attachment, this.#format.features, this.#format.styles.card)).filter(Boolean)

        if (request) this.#requests.delete(request)
        if (! this.#editor) return
        if (request && (nodes.length || ! request.keep)) this.#editor.commands.removeUpload?.(request.id)

        // None: nothing was chosen.
        if (! nodes.length) return this.#editor.commands.focus()

        const size = this.#editor.state.doc.content.size
        const { from, to } = request ?? this.#editor.state.selection

        this.#editor.chain().focus().insertContentAt({ from: Math.min(from, size), to: Math.min(to, size) }, nodes).run()
    }

    // A picture that couldn't be pasted, uploaded where its placeholder stands.
    #uploadMissing(id) {
        const [placeholder] = uploadsKey.getState(this.#editor.state)?.find(undefined, undefined, spec => spec.id === id) ?? []

        if (placeholder) this.#request([], { from: placeholder.from, to: placeholder.from }, ['image'], id)
    }

    // The images the app has no copy of, by address, each once (for the footer, kept
    // until the document changes).
    #unsavedImages() {
        const { doc } = this.#editor.state

        if (this.#unsaved.doc !== doc) this.#unsaved = { doc, images: this.#has('image') ? unsavedImages(doc, this.#hosts) : [] }

        return this.#unsaved.images
    }

    /**
     * Asks the app to save these images, someone having asked to: external ones by
     * address (editor-external-images { urls, replace }), data: ones as files
     * (editor-data-images { files, replace }). Until it answers they show as saving; the
     * attachments it answers with, in their order (null for one it didn't save), take
     * their place wherever they are by then. One it saved before takes its copy at once.
     */
    #save(sources) {
        if (! this.#editor) return

        this.#swapImages(new Map(sources.filter(src => this.#saved.has(src)).map(src => [src, this.#saved.get(src)])))

        const waiting = new Set(this.#saves.flatMap(request => request.sources))

        sources = [...new Set(sources)].filter(src => ! waiting.has(src) && ! this.#saved.has(src))

        for (const kind of ['external', 'data']) {
            const asked = sources.filter(src => unsaved(src, this.#hosts) === kind)

            if (! asked.length) continue

            const request = { kind, sources: asked }
            const replace = attachments => this.#replaceImages(attachments, request)

            this.#saves.push(request)
            this.dispatchEvent(new CustomEvent(`editor-${kind}-images`, {
                bubbles: true,
                detail: kind === 'external' ? { urls: [...asked], replace } : { files: asked.map(dataFile), replace },
            }))
        }

        this.#editor.commands.setSaving(this.#saves.flatMap(request => request.sources))
    }

    #replaceImages(attachments, request) {
        if (! request || ! this.#saves.includes(request) || ! this.#editor) return

        const answers = new Map(request.sources.map((src, index) => [src, attachments?.[index]]).filter(([, attachment]) => attachment?.url && safeUrl(attachment.url)))

        // A turned image saved still turns from its first picture.
        answers.forEach((attachment, src) => {
            this.#saved.set(src, attachment)
            if (this.#turns.has(src)) this.#turns.set(attachment.url, this.#turns.get(src))
        })
        this.#saves = this.#saves.filter(waiting => waiting !== request)
        this.#swapImages(answers)
        this.#editor.commands.setSaving(this.#saves.flatMap(waiting => waiting.sources))
    }

    // The images at these addresses take the app's copies, a step of its own to undo.
    #swapImages(answers) {
        if (! answers.size || ! this.#editor) return

        const { tr } = this.#editor.state

        tr.doc.descendants((node, position) => {
            const attachment = node.type.name === 'image' && answers.get(node.attrs.src)

            if (attachment) tr.setNodeMarkup(position, undefined, { ...node.attrs, src: attachment.url, alt: node.attrs.alt || attachment.alt || null, width: node.attrs.width ?? attachment.width ?? null, height: node.attrs.height ?? attachment.height ?? null })
        })

        if (tr.docChanged) this.#editor.view.dispatch(closeHistory(tr))
    }

    /**
     * Turns the selected image by a quarter, as a new picture (data:, to save as pasted
     * ones are). Turned again, it is turned from the first picture, so it loses nothing;
     * turned back, it is that picture again. Its width becomes its height, so it stands
     * as large; one that can't be read (another site's, which doesn't share it) says so.
     */
    async #rotate(degrees) {
        const { selection } = this.#editor.state
        const node = selection.node
        const position = { from: selection.from, to: selection.from }

        if (node?.type.name !== 'image') return

        const turn = this.#turns.get(node.attrs.src) ?? { src: node.attrs.src, width: node.attrs.width, height: node.attrs.height, rotation: 0 }
        const rotation = (turn.rotation + degrees + 360) % 360
        const box = this.#editor.view.nodeDOM(selection.from)?.querySelector?.('img')?.getBoundingClientRect()
        let picture = { src: turn.src }

        this.#tracked.add(position)

        try {
            if (rotation) picture = await rotateImage(turn.src, rotation)
        } catch {
            this.#all('[data-editor-image-error]').forEach(error => (error.hidden = false))

            return
        } finally {
            this.#tracked.delete(position)
        }

        const current = this.#editor?.state.doc.nodeAt(position.from)

        // Gone, or changed meanwhile: it is no longer the one to turn.
        if (current?.type.name !== 'image' || current.attrs.src !== node.attrs.src) return

        if (rotation) this.#turns.set(picture.src, { ...turn, rotation })

        const [width, height] = rotation % 180 ? [turn.height, turn.width] : [turn.width, turn.height]
        const style = box?.width && box.height ? this.#turned(current.attrs.style, box.height / box.width) : current.attrs.style

        this.#editor.chain().setNodeSelection(position.from).updateAttributes('image', { src: picture.src, width, height, style }).run()
    }

    // A style's width for the image turned, height over width its shape before: a width
    // in px is its height now, a percentage scaled the same, up to the full width.
    #turned(style, shape) {
        const width = computed(style, 'width')

        if (! width) return style
        if (width.endsWith('%')) return restyle(style, { width: `${Math.min(100, Math.round(parseFloat(width) * shape))}%` })
        if (width.endsWith('px')) return restyle(style, { width: `${Math.round(parseFloat(width) * shape)}px` })

        return style
    }

    // Sets the selected image's link, or takes it off for none.
    #imageLink(popover, href) {
        href = href.trim()

        if (this.#editor.state.selection.node?.type.name === 'image' && (! href || safeUrl(href))) this.#editor.chain().focus().updateAttributes('image', { href: href || null }).run()
        popover?.hidePopover()
    }

    /**
     * The format painter: picks up the selection's marks (but links) and its block's
     * style, and puts them on the next text selected, once.
     */
    #pick(on) {
        const { state } = this.#editor

        this.#painter = on ? { marks: (state.storedMarks ?? state.selection.$from.marks()).filter(mark => mark.type.name !== 'link'), style: blockStyle(this.#editor) } : null
        this.toggleAttribute('data-painting', on)
        this.#sync()
    }

    #paint() {
        const { state, view } = this.#editor ?? {}

        if (! this.#painter || ! state || state.selection.empty) return

        const { from, to } = state.selection
        const { tr } = state

        Object.values(state.schema.marks).filter(type => type.name !== 'link').forEach(type => tr.removeMark(from, to, type))
        this.#painter.marks.forEach(mark => tr.addMark(from, to, mark))
        state.doc.nodesBetween(from, to, (node, position) => {
            if (! ['paragraph', 'heading'].includes(node.type.name)) return

            tr.setNodeMarkup(position, undefined, { ...node.attrs, style: this.#painter.style })

            return false
        })

        view.dispatch(tr)
        this.#pick(false)
    }

    // What Word copied as RTF, for the paste or drop under way (its HTML parsed next).
    #readRtf(data) {
        this.#rtf = data?.types?.includes?.('text/rtf') ? data.getData('text/rtf') : ''

        return false
    }

    #paste(view, event, slice) {
        const data = event.clipboardData

        this.#rtf = ''

        if (! data) return false

        const text = data.getData('text/plain')
        const html = data.getData('text/html')

        // Files alone (a screenshot, files copied in a file manager): the app's to upload.
        if (data.files.length && ! text && ! html) {
            event.preventDefault()
            this.#request(data.files)

            return true
        }

        // Blocks pasted on an empty line take its place whole, their own style kept,
        // rather than the first and last running into it and taking its style (none). A
        // list's empty item keeps them in the list, as its items.
        const { $from, empty } = view.state.selection

        if (html && empty && $from.parent.isTextblock && ! $from.parent.content.size && slice.openStart && slice.content.firstChild?.isBlock && ! ['listItem', 'taskItem'].includes($from.node(-1).type.name)) {
            view.dispatch(view.state.tr.replaceSelection(new Slice(slice.content, 0, 0)).scrollIntoView().setMeta('paste', true).setMeta('uiEvent', 'paste'))

            return true
        }

        if (html || ! text) return false

        // A video's link alone: its player.
        const video = /^\S+$/.test(text.trim()) ? videoNode(text, this.#format.features, this.#format.platforms) : null

        if (video) {
            this.#editor.chain().focus().insertContent(video).run()

            return true
        }

        if (this.#has('markdown') && MARKDOWN.test(text)) {
            this.#editor.chain().focus().insertContent(marked.parse(text, { gfm: true, async: false })).run()

            return true
        }

        return false
    }

    #drop(view, event, moved) {
        const files = event.dataTransfer?.files

        this.#rtf = ''

        if (moved || ! files?.length) return false

        const position = view.posAtCoords({ left: event.clientX, top: event.clientY })?.pos ?? view.state.selection.from

        event.preventDefault()
        this.#request(files, { from: position, to: position })

        return true
    }

    #find(open) {
        const bar = this.#part('editor-find')

        if (! bar) return

        bar.hidden = ! open
        this.querySelector('[data-editor-action="find"]')?.setAttribute('aria-pressed', String(open))

        if (open) {
            const input = bar.querySelector('[data-editor-find-input]')
            const { from, to } = this.#editor.state.selection
            const selected = this.#editor.state.doc.textBetween(from, to)

            if (selected && ! selected.includes('\n')) input.value = selected
            input.focus()
            input.select()
            this.#search()
        } else {
            this.#editor.chain().focus().find('').run()
        }
    }

    #search(caseSensitive) {
        const toggle = this.querySelector('[data-editor-action="find-case"]')

        if (caseSensitive !== undefined) toggle?.setAttribute('aria-pressed', String(caseSensitive))

        this.#editor.commands.find(this.#field('editor-find-input'), toggle?.getAttribute('aria-pressed') === 'true')
    }

    #source(on) {
        const textarea = this.#part('editor-source')
        const content = this.#part('editor-content')

        if (! textarea) return

        if (on) textarea.value = lines(this.value)
        else this.#editor.commands.setContent(this.#whitelist.restrict(textarea.value), { emitUpdate: true })

        this.toggleAttribute('data-source', on)
        textarea.hidden = ! on
        content.hidden = on
        on ? textarea.focus() : this.#editor.commands.focus()
        this.#sync()
    }

    #fullscreen(on) {
        if (this.hasAttribute('data-fullscreen') === on) return

        this.toggleAttribute('data-fullscreen', on)
        document.documentElement.style.overflow = on ? 'hidden' : ''
        this.#sync()
        if (this.#editor && ! this.hasAttribute('data-source')) this.#editor.commands.focus()
    }

    // The tooltip names the control and its shortcut, anchored to it.
    #tip(control) {
        const tip = this.querySelector(':scope > [data-editor-tip]')

        if (! control || ! tip || control.dataset.tooltip !== tip.id) return

        const name = `--${tip.id}`
        const names = element => (element.style.getPropertyValue('anchor-name') || '').split(',').map(part => part.trim()).filter(part => part && part !== 'none')

        if (this.#anchor && this.#anchor !== control) this.#anchor.style.setProperty('anchor-name', names(this.#anchor).filter(part => part !== name).join(', ') || 'none')
        if (! names(control).includes(name)) control.style.setProperty('anchor-name', [...names(control), name].join(', '))

        this.#anchor = control
        tip.querySelector('[data-editor-tip-label]').textContent = control.getAttribute('aria-label') ?? ''

        const shortcut = tip.querySelector('[data-editor-tip-keys]')

        if (shortcut) {
            shortcut.textContent = control.dataset.editorKeys ? keys(control.dataset.editorKeys) : ''
            shortcut.hidden = ! control.dataset.editorKeys
        }
    }

    // The bars of controls: the toolbar, and the bubble menus.
    #bars() {
        return [this.#part('editor-toolbar'), ...this.#menus.filter(menu => menu.matches('[data-slot="editor-bubble"][role="toolbar"]'))].filter(Boolean)
    }

    // A bar's controls that can be focused, minus those in its menus and popovers (More
    // being a bar of its own).
    #controlsOf(bar) {
        const own = bar.matches('[popover]') ? bar : null

        return [...bar.querySelectorAll('button')].filter(button => button.closest('[popover]') === own && ! button.disabled && ! button.closest('[hidden]'))
    }

    // Alt+F10: the bubble menu showing, else the toolbar, at its tab stop.
    #enterBars() {
        const shown = bar => bar.isConnected && ! bar.closest('[hidden]') && getComputedStyle(bar).visibility !== 'hidden'
        const bar = this.#bars().find(one => one.matches('[data-slot="editor-bubble"]') && shown(one)) ?? this.#part('editor-toolbar')
        const controls = bar ? this.#controlsOf(bar) : []

        ;(controls.find(control => control.getAttribute('tabindex') === '0') ?? controls[0])?.focus()
    }

    // One control of each bar is its tab stop: the first, until another is moved to.
    // Those in More are a list of their own, tabbed through.
    #rovingStart() {
        for (const bar of this.#bars()) {
            const controls = this.#controlsOf(bar)
            const current = controls.find(control => control.getAttribute('tabindex') === '0') ?? controls[0]

            for (const button of bar.querySelectorAll('button')) {
                const popover = button.closest('[popover]')
                const index = button === current ? '0' : '-1'

                if (popover?.matches('[data-editor-more-content]')) button.removeAttribute('tabindex')
                else if (! popover && button.getAttribute('tabindex') !== index) button.setAttribute('tabindex', index)
            }
        }
    }

    // Left and right along a bar, Home and End to its ends; it keeps the tab stop.
    #rove(event, bar) {
        const controls = this.#controlsOf(bar)
        const index = controls.indexOf(event.target)
        const next = { ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: controls.length - 1 }[event.key]
        const control = controls[(next + controls.length) % controls.length]

        if (! control) return

        event.preventDefault()
        controls.forEach(button => (button.tabIndex = -1))
        control.tabIndex = 0
        control.focus()
    }

    // Arrow keys in rows (More's, swatches'): along the row, or to the row before or
    // after, as near the same place as it has.
    #step(event, rows) {
        rows = rows.filter(row => row.length)

        const row = rows.findIndex(controls => controls.includes(event.target))
        const [down, across] = STEPS[event.key]
        const target = rows[row + down]

        if (row < 0 || ! target) return

        event.preventDefault()
        target[Math.max(0, Math.min(target.length - 1, rows[row].indexOf(event.target) + across))]?.focus()
    }

    // Runs a command on a chain, or says whether it could; one the format lacks can't.
    #can(name, value, chain) {
        try {
            return COMMANDS[name]?.run(chain, value, this.#editor).run() ?? false
        } catch {
            return false
        }
    }

    #aria() {
        return Object.fromEntries(['aria-invalid', 'aria-describedby']
            .filter(name => this.hasAttribute(name))
            .map(name => [name, this.getAttribute(name)]))
    }

    /**
     * The controls show what is on at the selection and what can be done there. What can
     * be done depends on where the selection is, so it is asked again when that changes
     * (or when a menu or popover opens: force), undo and redo every time; controls out of
     * sight wait to be shown, but a menu's items still say which is current.
     */
    #sync(force = false) {
        const editor = this.#editor

        if (! editor) return

        const editable = editor.isEditable && ! this.hasAttribute('data-source')
        const { selection } = editor.state
        const path = $position => Array.from({ length: $position.depth + 1 }, (_, depth) => $position.node(depth).type.name).join('/')
        const context = [editable, selection.toJSON().type, selection.empty, selection.node?.type.name, path(selection.$from), path(selection.$to)].join('|')
        const fresh = force || context !== this.#context
        const shown = control => control.isConnected && (! control.closest('[popover]') || control.closest('[popover]').matches(':popover-open'))

        this.#context = context

        for (const control of this.#all('[data-editor-command]')) {
            const name = control.dataset.editorCommand
            const command = COMMANDS[name]
            const value = control.dataset.editorValue ?? ''
            const visible = command && shown(control)

            if (! command || (! visible && control.getAttribute('role') !== 'menuitemradio')) continue

            if (visible && (fresh || name === 'undo' || name === 'redo')) control.disabled = ! editable || ! this.#can(name, value, editor.can().chain().focus())
            if (command.active) control.setAttribute(control.getAttribute('role') === 'menuitemradio' ? 'aria-checked' : 'aria-pressed', String(command.active(editor, value)))
        }

        // Menus show their checked item on their button: its icon, or its name (the menu's
        // own for its default, none).
        for (const trigger of this.#all('[data-editor-menu]')) {
            const menu = document.getElementById(trigger.getAttribute('commandfor') ?? trigger.getAttribute('popovertarget') ?? '')
            const checked = menu?.querySelector('[aria-checked="true"]')
            const current = trigger.querySelector('[data-editor-current]')

            const label = checked?.dataset.editorLabel ?? checked?.textContent.trim() ?? ''

            trigger.disabled = ! editable
            if (current) current.textContent = (checked?.dataset.editorValue ? label : null) ?? current.dataset.editorDefault ?? ''
            // Its name says what it is; what it is set to, said after it.
            if (trigger.getAttribute('aria-description') !== label) label ? trigger.setAttribute('aria-description', label) : trigger.removeAttribute('aria-description')
            for (const icon of trigger.querySelectorAll('[data-editor-icon]')) icon.toggleAttribute('hidden', icon.dataset.editorIcon !== (checked?.dataset.editorValue ?? ''))
        }

        for (const control of this.#all('[data-editor-opens], [data-editor-action="media"], [data-editor-action^="rotate-"]')) {
            control.disabled = ! editable || (control.dataset.editorOpens === 'link' && editor.state.selection.empty && ! editor.isActive('link'))
            if (control.dataset.editorOpens === 'link') control.setAttribute('aria-pressed', String(editor.isActive('link')))
        }

        for (const control of this.querySelectorAll('[data-editor-action="find"]')) control.disabled = ! editable

        this.querySelector('[data-editor-action="source"]')?.setAttribute('aria-pressed', String(this.hasAttribute('data-source')))
        this.querySelector('[data-editor-action="format-painter"]')?.setAttribute('aria-pressed', String(!! this.#painter))
        this.querySelector('[data-editor-action="fullscreen"]')?.setAttribute('aria-pressed', String(this.hasAttribute('data-fullscreen')))

        this.#syncSelection(editor)
        this.#syncFind(editor)
        this.#syncCount(editor)
        this.#syncUnsaved(editor, editable)
        this.#rovingStart()
    }

    // The media, formula and link menus' parts and fields, for the node selected or the link.
    #syncSelection(editor) {
        const node = editor.state.selection.node ?? null
        const type = node?.type.name
        const href = editor.getAttributes('link').href ?? ''

        for (const part of this.#all('[data-editor-for]')) part.hidden = ! part.dataset.editorFor.split(' ').includes(type)

        // An image the app has no copy of can be saved to it.
        for (const save of this.#all('[data-editor-action="save-image"]')) {
            save.hidden = type !== 'image' || ! unsaved(node.attrs.src, this.#hosts)
            save.disabled = ! editor.isEditable || !! savingKey.getState(editor.state)?.pending.has(node?.attrs.src)
        }

        for (const link of this.#all('[data-editor-link-href]')) {
            if (link.getAttribute('href') !== href) link.setAttribute('href', href)
            link.textContent = href
        }

        if (node === this.#selected) return

        this.#selected = node
        this.#all('[data-editor-image-error]').forEach(error => (error.hidden = true))

        const [alt] = this.#all('[data-editor-alt-input]')
        const math = this.#all('[data-editor-math-form]').find(form => ! form.closest('[popover]'))

        if (alt && type === 'image') alt.value = node.attrs.alt ?? ''
        if (math && MATH.includes(type)) this.#fillMath(math, node.attrs.latex, type === 'blockMath')
    }

    #syncFind(editor) {
        const count = this.querySelector('[data-editor-find-count]')
        const state = findKey.getState(editor.state)

        if (! count || ! state) return

        const { matches, current, query } = state

        count.textContent = query ? `${matches.length ? current + 1 : 0}/${matches.length}` : ''

        for (const control of this.querySelectorAll('[data-editor-action^="find-"]:not([data-editor-action="find-case"]):not([data-editor-action="find-close"]), [data-editor-action^="replace"]')) {
            control.disabled = ! matches.length || (control.dataset.editorAction.startsWith('replace') && ! editor.isEditable)
        }
    }

    #syncCount(editor) {
        const count = this.querySelector('[data-editor-count]')

        if (! count || ! editor.storage.characterCount) return

        const characters = editor.storage.characterCount.characters()
        const max = Number(this.dataset.maxLength) || null

        count.textContent = max ? `${characters} / ${max}` : String(characters)
        count.toggleAttribute('data-full', !! max && characters >= max)
    }

    // The footer says how many images the app has no copy of, to save them all.
    #syncUnsaved(editor, editable) {
        const notice = this.querySelector('[data-editor-unsaved]')

        if (! notice) return

        const images = this.#unsavedImages()
        const saving = savingKey.getState(editor.state)?.pending ?? new Set()
        const label = notice.querySelector('[data-editor-unsaved-label]')
        const text = (notice.dataset.editorLabel ?? ':count').replace(':count', images.length)

        notice.hidden = ! images.length
        if (label.textContent !== text) label.textContent = text
        notice.querySelector('[data-editor-action="save-images"]').disabled = ! editable || images.every(src => saving.has(src))
    }
}

define('eso-editor', EditorElement)
