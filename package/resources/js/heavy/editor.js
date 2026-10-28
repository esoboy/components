/*
 * <eso-editor>
 *
 * A Tiptap editor (StarterKit with headings 1–3, links that don't open on click, no
 * trailing paragraph) on the HTML its view rendered, sanitized, into
 * [data-slot="editor-content"]. .value is the HTML, '' when empty: input comes from the
 * element on every change and change when it loses focus, so wire:model binds on it,
 * and data-name keeps a hidden input for forms.
 * The toolbar's [data-editor-command] buttons run their command and show whether it is
 * on (aria-pressed) or possible; the link popover sets or removes the selection's link.
 *
 * The server sanitizes both ways: the view what it renders, #[Sanitize] what comes back.
 * Built into dist/editor.js; the view loads it through @assets.
 */

import { Editor } from '@tiptap/core'
import { Placeholder } from '@tiptap/extensions'
import StarterKit from '@tiptap/starter-kit'
import { define, writeHidden } from '../support/element.js'

const COMMANDS = {
    'undo': { run: chain => chain.undo() },
    'redo': { run: chain => chain.redo() },
    'heading-1': { run: chain => chain.toggleHeading({ level: 1 }), active: ['heading', { level: 1 }] },
    'heading-2': { run: chain => chain.toggleHeading({ level: 2 }), active: ['heading', { level: 2 }] },
    'heading-3': { run: chain => chain.toggleHeading({ level: 3 }), active: ['heading', { level: 3 }] },
    'bullet-list': { run: chain => chain.toggleBulletList(), active: ['bulletList'] },
    'ordered-list': { run: chain => chain.toggleOrderedList(), active: ['orderedList'] },
    'blockquote': { run: chain => chain.toggleBlockquote(), active: ['blockquote'] },
    'code-block': { run: chain => chain.toggleCodeBlock(), active: ['codeBlock'] },
    'bold': { run: chain => chain.toggleBold(), active: ['bold'] },
    'italic': { run: chain => chain.toggleItalic(), active: ['italic'] },
    'underline': { run: chain => chain.toggleUnderline(), active: ['underline'] },
    'strike': { run: chain => chain.toggleStrike(), active: ['strike'] },
    'code': { run: chain => chain.toggleCode(), active: ['code'] },
    'horizontal-rule': { run: chain => chain.setHorizontalRule() },
}

export class EditorElement extends HTMLElement {
    static observedAttributes = ['aria-invalid', 'aria-describedby', 'data-disabled']

    #editor = null
    #focused = null

    connectedCallback() {
        if (this.#editor) return

        const content = this.querySelector('[data-slot="editor-content"]')

        if (! content) return

        const html = content.innerHTML

        content.replaceChildren()

        this.#editor = new Editor({
            element: content,
            content: html,
            editable: ! this.hasAttribute('data-disabled'),
            extensions: [
                // No trailing empty paragraph: the value stays the HTML that was written.
                StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: { openOnClick: false }, trailingNode: false }),
                Placeholder.configure({ placeholder: this.dataset.placeholder ?? '' }),
            ],
            editorProps: {
                attributes: () => ({
                    'data-slot': 'editor-input',
                    'role': 'textbox',
                    'aria-multiline': 'true',
                    'aria-label': this.dataset.label ?? '',
                    ...this.#aria(),
                }),
            },
            onUpdate: () => {
                writeHidden(this, this.dataset.name, this.value)
                this.dispatchEvent(new Event('input', { bubbles: true }))
            },
            onFocus: () => {
                this.#focused = this.value
            },
            onBlur: () => {
                if (this.#focused !== null && this.#focused !== this.value) this.dispatchEvent(new Event('change', { bubbles: true }))
                this.#focused = null
            },
            onTransaction: () => this.#sync(),
        })

        this.addEventListener('click', event => this.#click(event))
        this.addEventListener('keydown', event => {
            // Enter in the link field applies it, rather than submitting a form around the editor.
            if (event.key === 'Enter' && event.target.matches?.('[data-editor-link-input]')) {
                event.preventDefault()
                this.#link(event.target.value)
            }
        })
        this.addEventListener('toggle', event => this.#linkOpened(event), true)

        this.#sync()
    }

    disconnectedCallback() {
        // Moved, it comes back at once; removed, its editor goes.
        queueMicrotask(() => {
            if (this.isConnected) return

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

    // From the server (wire:model): its HTML replaces ours, without reporting back.
    set value(value) {
        value ??= ''

        if (! this.#editor || value === this.value) return

        this.#editor.commands.setContent(value, { emitUpdate: false })
        writeHidden(this, this.dataset.name, this.value)
    }

    #aria() {
        return Object.fromEntries(['aria-invalid', 'aria-describedby']
            .filter(name => this.hasAttribute(name))
            .map(name => [name, this.getAttribute(name)]))
    }

    #click(event) {
        const button = event.target.closest?.('[data-editor-command], [data-editor-link-apply], [data-editor-link-remove]')

        if (! button || button.disabled) return

        if (button.hasAttribute('data-editor-link-apply')) return this.#link(this.querySelector('[data-editor-link-input]')?.value ?? '')
        if (button.hasAttribute('data-editor-link-remove')) return this.#link('')

        COMMANDS[button.dataset.editorCommand]?.run(this.#editor.chain().focus()).run()
    }

    // The toolbar shows what is on at the selection and what can be done there.
    #sync() {
        const editor = this.#editor

        for (const button of this.querySelectorAll('[data-editor-command]')) {
            const command = COMMANDS[button.dataset.editorCommand]

            if (! command) continue

            button.disabled = ! editor.isEditable || ! command.run(editor.can().chain().focus()).run()
            if (command.active) button.setAttribute('aria-pressed', String(editor.isActive(...command.active)))
        }

        const link = this.querySelector('[data-editor-link]')

        if (link) {
            link.disabled = ! editor.isEditable || (editor.state.selection.empty && ! editor.isActive('link'))
            link.setAttribute('aria-pressed', String(editor.isActive('link')))
        }
    }

    #linkOpened(event) {
        const input = event.target.querySelector?.('[data-editor-link-input]')

        if (! input || event.newState !== 'open') return

        input.value = this.#editor.getAttributes('link').href ?? ''
        input.select()
        this.querySelector('[data-editor-link-remove]')?.toggleAttribute('hidden', ! this.#editor.isActive('link'))
    }

    // Sets the selection's link to the address, or removes it for none.
    #link(href) {
        const chain = this.#editor.chain().focus().extendMarkRange('link')

        href = href.trim()
        ;(href ? chain.setLink({ href }) : chain.unsetLink()).run()

        this.querySelector('[data-editor-link-input]')?.closest('[popover]')?.hidePopover()
    }
}

define('eso-editor', EditorElement)
