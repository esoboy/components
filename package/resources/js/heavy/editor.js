/*
 * <eso-editor>
 *
 * A Tiptap editor on the HTML its view rendered, sanitized, into [data-slot="editor-content"],
 * writing what data-format allows (its features, palette, fonts, …, from the server) with
 * the tools data-tools lists. .value is the HTML, '' when empty: input comes from the
 * element on every change and change when it loses focus, so wire:model binds on it, and
 * data-name keeps a hidden input for forms.
 *
 * Controls: [data-editor-command] (and data-editor-value) runs a command and shows whether
 * it is on (aria-pressed, aria-checked in menus) or possible; [data-editor-action] opens
 * what the editor has besides (media, find, source, full screen). The toolbar is one tab
 * stop, arrow keys moving within it, and one tooltip names whichever control is pointed at.
 *
 * Media: the editor uploads nothing. It asks with editor-media { files, accept, insert }
 * (files dropped or pasted, or none from its button) and inserts what comes back, in
 * place, through detail.insert(attachments) or Ui::editor(id)->insert(): an array of
 * { url, name, mime, size, alt, width, height, poster }, or none for nothing chosen.
 *
 * Built into dist/editor.js; the view loads it through @assets.
 */

import { Editor, createDocument } from '@tiptap/core'
import BubbleMenu from '@tiptap/extension-bubble-menu'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import Typography from '@tiptap/extension-typography'
import { CharacterCount, Placeholder } from '@tiptap/extensions'
import { TextSelection } from '@tiptap/pm/state'
import { CellSelection } from '@tiptap/pm/tables'
import StarterKit from '@tiptap/starter-kit'
import { marked } from 'marked'
import { define, writeHidden } from '../support/element.js'
import { clone, readTemplates } from '../support/templates.js'
import { COMMANDS } from './editor/commands.js'
import { dragHandle } from './editor/drag.js'
import { Find, findKey } from './editor/find.js'
import { accepted, attachmentNode, embedSource } from './editor/media.js'
import { Audio, PlainCodeBlock, Picture, Video, collapsible, embed, file, formulas, tables, tasks, toMathML } from './editor/nodes.js'
import { ClearFormatting, cleanPastedHTML, styleExtensions } from './editor/styles.js'
import { emojiMenu, slashMenu } from './editor/suggest.js'

const MAC = /Mac|iPhone|iPad/.test(navigator.platform ?? '')
const MEDIA = ['image', 'video', 'audio', 'file', 'embed']
const MATH = ['inlineMath', 'blockMath']
const ROVE = ['ArrowLeft', 'ArrowRight', 'Home', 'End']

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

export class EditorElement extends HTMLElement {
    static observedAttributes = ['aria-invalid', 'aria-describedby', 'data-disabled']

    #editor = null
    #focused = null
    #format = null
    #tools = []
    #requests = new Set()
    #selected = null
    #anchor = null
    #menus = []
    #mathPreview = 0

    connectedCallback() {
        if (this.#editor) {
            window.addEventListener('ui-editor', this.#server)

            return
        }

        const content = this.querySelector(':scope > [data-slot="editor-content"]')

        if (! content) return

        this.#format = { features: [], headings: [1, 2, 3, 4], colors: [], highlights: [], fonts: [], sizes: [], lineHeights: [], indents: 8, platforms: [], styles: {}, ...JSON.parse(this.dataset.format || '{}') }
        this.#tools = (this.dataset.tools ?? 'toolbar').split(/\s+/).filter(Boolean)

        const html = content.innerHTML

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
                    ...this.#aria(),
                }),
                transformPastedHTML: cleanPastedHTML,
                handlePaste: (view, event) => this.#paste(event),
                handleDrop: (view, event, slice, moved) => this.#drop(view, event, moved),
            },
            onUpdate: () => {
                writeHidden(this, this.dataset.name, this.value)
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
                for (const request of this.#requests) {
                    request.from = transaction.mapping.map(request.from)
                    request.to = transaction.mapping.map(request.to)
                }

                this.#sync()
            },
        })

        this.#listen()
        this.#prepareControls(this)
        this.#sync()
    }

    disconnectedCallback() {
        window.removeEventListener('ui-editor', this.#server)

        // Moved, it comes back at once; removed, its editor goes.
        queueMicrotask(() => {
            if (this.isConnected) return

            this.#fullscreen(false)
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

    get value() {
        if (! this.#editor) return this.querySelector('[data-slot="editor-content"]')?.innerHTML.trim() ?? ''

        return this.#editor.isEmpty ? '' : this.#editor.getHTML()
    }

    // From the server (wire:model): its HTML replaces ours, without reporting back. The
    // same content written differently (the server's sanitized copy) leaves it be.
    set value(value) {
        value ??= ''

        if (! this.#editor || value === this.value || createDocument(value, this.#editor.schema).eq(this.#editor.state.doc)) return

        this.#editor.commands.setContent(value, { emitUpdate: false })
        writeHidden(this, this.dataset.name, this.value)
    }

    /**
     * Inserts attachments where the cursor is, as an answer to editor-media does where it
     * was asked: images, audio and video as themselves, the rest as files.
     */
    insert(attachments) {
        this.#insert(attachments, null)
    }

    #has(feature) {
        return this.#format.features.includes(feature)
    }

    #tool(tool) {
        return this.#tools.includes(tool)
    }

    #extensions() {
        const has = feature => this.#has(feature)
        const templates = readTemplates(this)
        const lists = has('bullet-list') || has('ordered-list')
        const max = Number(this.dataset.maxLength) || null
        const labels = { expand: 'Expand', collapse: 'Collapse', task: 'Done', ...JSON.parse(this.dataset.labels || '{}') }

        return [
            // No trailing empty paragraph: the value stays the HTML that was written.
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
                trailingNode: false,
            }),
            Placeholder.configure({ placeholder: this.dataset.placeholder ?? '' }),
            styleExtensions(this.#format),
            ClearFormatting,
            has('superscript') && Superscript,
            has('subscript') && Subscript,
            has('task-list') && tasks(labels),
            has('details') && collapsible(labels),
            has('code-block') && PlainCodeBlock,
            has('table') && tables(this.#format.styles),
            has('image') && Picture,
            has('video') && Video,
            has('audio') && Audio,
            has('file') && file(this.#format.styles.card),
            has('embed') && embed(this.#format.platforms),
            has('math') && formulas(),
            this.#tool('find-replace') && Find,
            this.#tool('character-count') && CharacterCount.configure({ limit: max, textCounter: characters }),
            this.#tool('typography') && Typography,
            this.#tool('drag-handle') && templates['drag-handle'] && dragHandle(this.#menu(clone(templates['drag-handle']))),
            this.#tool('slash-menu') && templates.slash && slashMenu(templates.slash, option => this.#run(option)),
            has('emoji') && this.dataset.emoji && templates.emoji && emojiMenu(templates.emoji, this.dataset.emoji),
            this.#tool('bubble-menu') && this.#bubbles(templates),
        ].flat().filter(Boolean)
    }

    // The bubble menus: for selected text, in a table, on media, on a formula.
    #bubbles(templates) {
        const node = state => state.selection.node?.type.name
        const menus = {
            text: ({ editor, view, state, from, to, element }) => state.selection instanceof TextSelection && ! state.selection.empty && !! state.doc.textBetween(from, to).trim() && ! editor.isActive('codeBlock') && focused(view, element),
            table: ({ editor, view, state, element }) => editor.isActive('table') && (state.selection.empty || state.selection instanceof CellSelection) && focused(view, element),
            media: ({ view, state, element }) => MEDIA.includes(node(state)) && focused(view, element),
            math: ({ view, state, element }) => MATH.includes(node(state)) && focused(view, element),
        }

        return Object.entries(menus).filter(([name]) => templates[`bubble-${name}`]).map(([name, shouldShow]) => BubbleMenu.extend({ name: `bubble-${name}` }).configure({
            pluginKey: `bubble-${name}`,
            element: this.#menu(clone(templates[`bubble-${name}`])),
            updateDelay: 100,
            shouldShow: props => props.editor.isEditable && shouldShow(props),
            // The table's menu sits above the table, not the cursor.
            getReferencedVirtualElement: name === 'table' ? () => this.#tableRect() : undefined,
            options: { placement: 'top', offset: 8, flip: {}, shift: { padding: 8 } },
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
        if (action === 'insert-content' && html) this.#editor.chain().focus().insertContent(html).run()
        if (action === 'focus') this.#editor.commands.focus()
    }

    // What a control does.
    #run(control) {
        const { editorCommand: command, editorValue: value = '', editorAction: action } = control.dataset
        const editor = this.#editor

        if (command) {
            this.#can(command, value, editor.chain().focus())
            // A swatch or an item picked in a popover closes it.
            control.closest('[popover]')?.hidePopover()

            return
        }

        if (action === 'media') this.#request()
        if (action === 'find') this.#find(this.#part('editor-find')?.hidden ?? false)
        if (action === 'source') this.#source(! this.hasAttribute('data-source'))
        if (action === 'fullscreen') this.#fullscreen(! this.hasAttribute('data-fullscreen'))
        if (action === 'link-apply') this.#link(control.closest('[popover]'), control.closest('[popover]')?.querySelector('[data-editor-link-input]')?.value ?? '')
        if (action === 'link-remove') this.#link(control.closest('[popover]'), '')
        if (action === 'embed-apply') this.#embed(control.closest('[popover]'))
        if (action === 'math-apply') this.#math(control.closest('[data-editor-math-form]'))
        if (action === 'find-next') editor.commands.findNext(1)
        if (action === 'find-previous') editor.commands.findNext(-1)
        if (action === 'find-case') this.#search(control.getAttribute('aria-pressed') !== 'true')
        if (action === 'replace') editor.chain().replace(this.#field('editor-replace-input')).findNext(0).run()
        if (action === 'replace-all') editor.commands.replaceAll(this.#field('editor-replace-input'))
        if (action === 'find-close') this.#find(false)

        // A control in a menu or a popover opens one elsewhere: the toolbar's.
        if (action === 'open') this.querySelector(`[data-slot="editor-toolbar"] [data-editor-opens="${control.dataset.editorValue}"]`)?.click()
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

        if (event.key === 'Enter' && target.matches?.('[data-editor-embed-input]')) {
            event.preventDefault()
            this.#embed(target.closest('[popover]'))
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

        // Find opens over the browser's own, from the text or the bar.
        if ((event.key === 'f' || event.key === 'F') && (MAC ? event.metaKey : event.ctrlKey) && ! event.altKey && this.#part('editor-find') && this.contains(target)) {
            event.preventDefault()
            this.#find(true)
        }

        if (event.key === 'Escape' && this.hasAttribute('data-fullscreen') && ! event.defaultPrevented && ! document.querySelector('[popover]:not([popover="manual"]):popover-open')) {
            event.preventDefault()
            this.#fullscreen(false)
        }

        if (ROVE.includes(event.key) && target.closest?.('[data-slot="editor-toolbar"]') && ! target.closest('[popover]') && ! target.matches('input, textarea')) this.#rove(event)
    }

    #typed(event) {
        const target = event.target

        if (target.matches?.('[data-editor-find-input]')) this.#search()
        if (target.matches?.('[data-slot="editor-source"]')) this.#editor.commands.setContent(target.value, { emitUpdate: true })
        if (target.matches?.('[data-editor-math-input], [data-editor-math-display]')) this.#previewMath(target.closest('[data-editor-math-form]'))
        if (target.matches?.('[data-editor-embed-input]')) target.removeAttribute('aria-invalid')
    }

    // A popover of the editor's opened: its fields start from the selection.
    #opened(event) {
        const popover = event.target

        if (event.newState !== 'open' || ! popover.matches?.('[popover]')) return

        const editor = this.#editor
        const link = popover.querySelector('[data-editor-link-input]')
        const embed = popover.querySelector('[data-editor-embed-input]')
        const math = popover.querySelector('[data-editor-math-form]')

        if (link) {
            link.value = editor.getAttributes('link').href ?? ''
            link.select()
            popover.querySelector('[data-editor-action="link-remove"]')?.toggleAttribute('hidden', ! editor.isActive('link'))
        }

        if (embed) {
            embed.value = ''
            embed.removeAttribute('aria-invalid')
        }

        if (math) this.#fillMath(math, '', false)

        this.#sync()
    }

    // Sets the selection's link to the address, or removes it for none.
    #link(popover, href) {
        const chain = this.#editor.chain().focus().extendMarkRange('link')

        href = href.trim()
        ;(href ? chain.setLink({ href }) : chain.unsetLink()).run()
        popover?.hidePopover()
    }

    #embed(popover) {
        const input = popover?.querySelector('[data-editor-embed-input]')
        const src = embedSource(input?.value, this.#format.platforms)

        if (! src) return input?.setAttribute('aria-invalid', 'true')

        this.#editor.chain().focus().insertContent({ type: 'embed', attrs: { src } }).run()
        popover.hidePopover()
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
    }

    #setAlt(alt) {
        if (this.#editor.state.selection.node?.type.name === 'image') this.#editor.commands.updateAttributes('image', { alt })
    }

    /**
     * Asks for media to insert, at the selection or where files were dropped: the answer,
     * any time later, goes where that place has moved to.
     */
    #request(files = [], range = null) {
        if (! accepted(this.#format.features).length) return

        const { from, to } = range ?? this.#editor.state.selection
        const request = { from, to }

        this.#requests.add(request)
        if (this.#requests.size > 20) this.#requests.delete(this.#requests.values().next().value)

        this.dispatchEvent(new CustomEvent('editor-media', {
            bubbles: true,
            detail: { files: [...files], accept: accepted(this.#format.features), insert: attachments => this.#insert(attachments, request) },
        }))
    }

    #insert(attachments, request) {
        const nodes = (Array.isArray(attachments) ? attachments : [attachments]).filter(Boolean).map(attachment => attachmentNode(attachment, this.#format.features)).filter(Boolean)

        if (request) this.#requests.delete(request)
        if (! this.#editor) return

        // None: nothing was chosen.
        if (! nodes.length) return this.#editor.commands.focus()

        const size = this.#editor.state.doc.content.size
        const { from, to } = request ?? this.#editor.state.selection

        this.#editor.chain().focus().insertContentAt({ from: Math.min(from, size), to: Math.min(to, size) }, nodes).run()
    }

    #paste(event) {
        const data = event.clipboardData

        if (! data) return false

        const text = data.getData('text/plain')
        const html = data.getData('text/html')

        // Files alone (a screenshot, files copied in a file manager): the app's to upload.
        if (data.files.length && ! text && ! html) {
            event.preventDefault()
            this.#request(data.files)

            return true
        }

        if (html || ! text) return false

        const src =this.#has('embed') && /^\S+$/.test(text.trim()) ? embedSource(text, this.#format.platforms) : null

        if (src) {
            this.#editor.chain().focus().insertContent({ type: 'embed', attrs: { src } }).run()

            return true
        }

        if (this.#tool('markdown') && this.#format.features.length && MARKDOWN.test(text)) {
            this.#editor.chain().focus().insertContent(marked.parse(text, { gfm: true, async: false })).run()

            return true
        }

        return false
    }

    #drop(view, event, moved) {
        const files = event.dataTransfer?.files

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
        else this.#editor.commands.setContent(textarea.value, { emitUpdate: true })

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

    // The toolbar's controls, minus those in its menus, that can be focused.
    #toolbarControls() {
        const toolbar = this.#part('editor-toolbar')

        return toolbar ? [...toolbar.querySelectorAll('button')].filter(button => ! button.closest('[popover]') && ! button.disabled && ! button.closest('[hidden]')) : []
    }

    // One control of the toolbar is its tab stop: the first, until another is moved to.
    #rovingStart() {
        const controls = this.#toolbarControls()

        if (! controls.length || controls.some(control => control.getAttribute('tabindex') === '0')) return

        this.#part('editor-toolbar').querySelectorAll('button').forEach(button => button.closest('[popover]') || (button.tabIndex = -1))
        controls[0].tabIndex = 0
    }

    #rove(event) {
        const controls = this.#toolbarControls()
        const index = controls.indexOf(event.target)
        const next = { ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: controls.length - 1 }[event.key]
        const control = controls[(next + controls.length) % controls.length]

        if (! control) return

        event.preventDefault()
        controls.forEach(button => (button.tabIndex = -1))
        control.tabIndex = 0
        control.focus()
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

    // The controls show what is on at the selection and what can be done there.
    #sync() {
        const editor = this.#editor

        if (! editor) return

        const editable = editor.isEditable && ! this.hasAttribute('data-source')
        const shown = control => ! control.closest('[popover]') || control.closest('[popover]').matches(':popover-open')

        for (const control of this.#all('[data-editor-command]')) {
            const command = COMMANDS[control.dataset.editorCommand]
            const value = control.dataset.editorValue ?? ''

            if (! command) continue

            // Menus closed are checked when they open.
            if (shown(control)) control.disabled = ! editable || ! this.#can(control.dataset.editorCommand, value, editor.can().chain().focus())
            if (command.active) control.setAttribute(control.getAttribute('role') === 'menuitemradio' ? 'aria-checked' : 'aria-pressed', String(command.active(editor, value)))
        }

        // Menus show their checked item on their button: its icon, or its name.
        for (const trigger of this.querySelectorAll('[data-editor-menu]')) {
            const menu = document.getElementById(trigger.getAttribute('commandfor') ?? trigger.getAttribute('popovertarget') ?? '')
            const checked = menu?.querySelector('[aria-checked="true"]')
            const current = trigger.querySelector('[data-editor-current]')

            trigger.disabled = ! editable
            if (current) current.textContent = checked?.dataset.editorLabel ?? checked?.textContent.trim() ?? current.dataset.editorDefault ?? ''
            for (const icon of trigger.querySelectorAll('[data-editor-icon]')) icon.toggleAttribute('hidden', icon.dataset.editorIcon !== (checked?.dataset.editorValue ?? ''))
        }

        for (const control of this.#all('[data-editor-opens], [data-editor-action="media"]')) {
            control.disabled = ! editable || (control.dataset.editorOpens === 'link' && editor.state.selection.empty && ! editor.isActive('link'))
            if (control.dataset.editorOpens === 'link') control.setAttribute('aria-pressed', String(editor.isActive('link')))
        }

        for (const control of this.querySelectorAll('[data-editor-action="find"]')) control.disabled = ! editable

        this.querySelector('[data-editor-action="source"]')?.setAttribute('aria-pressed', String(this.hasAttribute('data-source')))
        this.querySelector('[data-editor-action="fullscreen"]')?.setAttribute('aria-pressed', String(this.hasAttribute('data-fullscreen')))

        this.#syncSelection(editor)
        this.#syncFind(editor)
        this.#syncCount(editor)
        this.#rovingStart()
    }

    // The media and formula menus' parts and fields, for the node selected.
    #syncSelection(editor) {
        const node = editor.state.selection.node ?? null
        const type = node?.type.name

        for (const part of this.#all('[data-editor-for]')) part.hidden = ! part.dataset.editorFor.split(' ').includes(type)

        if (node === this.#selected) return

        this.#selected = node

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
        const labels = { expand: 'Expand', collapse: 'Collapse', task: 'Done', ...JSON.parse(this.dataset.labels || '{}') }

        count.textContent = max ? `${characters} / ${max}` : String(characters)
        count.toggleAttribute('data-full', !! max && characters >= max)
    }
}

define('eso-editor', EditorElement)
