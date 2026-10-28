import { afterEach, beforeAll, describe, expect, it } from 'vitest'

/*
 * ProseMirror measures the selection to scroll it into view; jsdom lays nothing out.
 */
beforeAll(async () => {
    const rect = () => ({ top: 0, left: 0, right: 0, bottom: 0, width: 0, height: 0, x: 0, y: 0 })

    Range.prototype.getClientRects = () => []
    Range.prototype.getBoundingClientRect = rect
    Element.prototype.getClientRects = () => []
    document.elementFromPoint = () => null

    await import('../../resources/js/heavy/editor.js')
})

afterEach(() => {
    document.body.innerHTML = ''
})

const COMMANDS = ['undo', 'redo', 'heading-1', 'heading-2', 'heading-3', 'bullet-list', 'ordered-list', 'blockquote', 'code-block', 'bold', 'italic', 'underline', 'strike', 'code', 'horizontal-rule']
const TOGGLES = ['heading-1', 'heading-2', 'heading-3', 'bullet-list', 'ordered-list', 'blockquote', 'code-block', 'bold', 'italic', 'underline', 'strike', 'code']

function mount(html = '<h2>Notes</h2><p>Hello world</p>', attributes = '') {
    document.body.innerHTML = `
        <form>
            <eso-editor data-slot="editor" data-placeholder="Write" data-label="Notes" data-name="body" ${attributes}>
                <div data-slot="editor-toolbar" role="toolbar">
                    ${COMMANDS.map(command => `<button type="button" data-editor-command="${command}" ${TOGGLES.includes(command) ? 'aria-pressed="false"' : ''}></button>`).join('')}
                    <button type="button" data-editor-link aria-pressed="false"></button>
                    <div id="link" popover="auto" data-editor-link-content>
                        <input type="url" data-editor-link-input>
                        <button type="button" data-editor-link-apply></button>
                        <button type="button" data-editor-link-remove></button>
                    </div>
                </div>
                <div data-slot="editor-content">${html}</div>
            </eso-editor>
        </form>`

    const element = document.querySelector('eso-editor')
    const events = []

    element.addEventListener('input', () => events.push('input'))
    element.addEventListener('change', () => events.push('change'))

    return [element, events]
}

const button = (element, command) => element.querySelector(`[data-editor-command="${command}"]`)
const select = (element, text) => {
    let from = 0

    element.editor.state.doc.descendants((node, position) => {
        if (node.isText && node.text.includes(text)) from = position + node.text.indexOf(text)
    })
    element.editor.commands.setTextSelection({ from, to: from + text.length })
}

describe('mounting', () => {
    it('edits the HTML its view rendered, as a labelled text box', () => {
        const [element, events] = mount()
        const input = element.querySelector('[data-slot="editor-input"]')

        expect(element.value).toBe('<h2>Notes</h2><p>Hello world</p>')
        expect(input.getAttribute('contenteditable')).toBe('true')
        expect(input.getAttribute('role')).toBe('textbox')
        expect(input.getAttribute('aria-multiline')).toBe('true')
        expect(input.getAttribute('aria-label')).toBe('Notes')
        expect(events).toEqual([])
    })

    it('is empty as an empty string, with its placeholder', () => {
        const [element] = mount('')

        expect(element.value).toBe('')
        expect(element.querySelector('p.is-editor-empty').dataset.placeholder).toBe('Write')
    })
})

describe('editing', () => {
    it('reports every change, keeps the hidden input, and reports a change on leaving', () => {
        const [element, events] = mount()
        const input = element.querySelector('[data-slot="editor-input"]')

        input.dispatchEvent(new FocusEvent('focus'))
        element.editor.commands.insertContentAt(element.editor.state.doc.content.size, '<p>More</p>')

        expect(element.value).toBe('<h2>Notes</h2><p>Hello world</p><p>More</p>')
        expect(element.querySelector('input[type="hidden"][name="body"]').value).toBe(element.value)
        expect(events).toEqual(['input'])

        input.dispatchEvent(new FocusEvent('blur'))

        expect(events).toEqual(['input', 'change'])
    })

    it('says nothing on leaving unchanged', () => {
        const [element, events] = mount()
        const input = element.querySelector('[data-slot="editor-input"]')

        input.dispatchEvent(new FocusEvent('focus'))
        input.dispatchEvent(new FocusEvent('blur'))

        expect(events).toEqual([])
    })
})

describe('the toolbar', () => {
    it('runs a command and shows it pressed', () => {
        const [element] = mount()

        select(element, 'world')
        button(element, 'bold').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello <strong>world</strong></p>')
        expect(button(element, 'bold').getAttribute('aria-pressed')).toBe('true')
        expect(button(element, 'italic').getAttribute('aria-pressed')).toBe('false')
    })

    it('shows the block at the selection and what can be done there', () => {
        const [element] = mount()

        select(element, 'Notes')

        expect(button(element, 'heading-2').getAttribute('aria-pressed')).toBe('true')
        expect(button(element, 'undo').disabled).toBe(true)

        button(element, 'heading-2').click()

        expect(element.value).toBe('<p>Notes</p><p>Hello world</p>')
        expect(button(element, 'heading-2').getAttribute('aria-pressed')).toBe('false')
        expect(button(element, 'undo').disabled).toBe(false)

        button(element, 'undo').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello world</p>')
    })

    it('turns everything off while disabled', () => {
        const [element] = mount(undefined, 'data-disabled')

        expect(element.editor.isEditable).toBe(false)
        expect(element.querySelectorAll('[data-editor-command]:not(:disabled)')).toHaveLength(0)
        expect(element.querySelector('[data-editor-link]').disabled).toBe(true)

        element.removeAttribute('data-disabled')

        expect(element.editor.isEditable).toBe(true)
        expect(button(element, 'bold').disabled).toBe(false)
    })
})

describe('links', () => {
    it('opens on the selection\'s link and applies an address with Enter, not submitting the form', () => {
        const [element] = mount('<p>Read the <a href="https://old.test">docs</a> now</p>')
        const popover = element.querySelector('#link')
        const input = element.querySelector('[data-editor-link-input]')
        let submitted = false

        element.closest('form').addEventListener('submit', event => {
            submitted = true
            event.preventDefault()
        })
        select(element, 'docs')

        expect(element.querySelector('[data-editor-link]').getAttribute('aria-pressed')).toBe('true')

        popover.showPopover()

        expect(input.value).toBe('https://old.test')
        expect(element.querySelector('[data-editor-link-remove]').hidden).toBe(false)

        input.value = 'https://laravel.com'

        const enter = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true })

        input.dispatchEvent(enter)

        expect(enter.defaultPrevented).toBe(true)
        expect(submitted).toBe(false)
        expect(popover.matches(':popover-open')).toBe(false)
        expect(element.value).toContain('href="https://laravel.com">docs</a>')
    })

    it('sets a link on a selection and removes it', () => {
        const [element] = mount()
        const popover = element.querySelector('#link')

        select(element, 'world')
        popover.showPopover()

        expect(element.querySelector('[data-editor-link-remove]').hidden).toBe(true)

        element.querySelector('[data-editor-link-input]').value = 'https://tiptap.dev'
        element.querySelector('[data-editor-link-apply]').click()

        expect(element.value).toContain('<a target="_blank" rel="noopener noreferrer nofollow" href="https://tiptap.dev">world</a>')

        select(element, 'world')
        element.querySelector('[data-editor-link-remove]').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello world</p>')
    })

    it('needs a selection or a link to start from', () => {
        const [element] = mount()

        element.editor.commands.setTextSelection(3)

        expect(element.querySelector('[data-editor-link]').disabled).toBe(true)
    })
})

describe('the server', () => {
    it('replaces the content without hearing back, and an echo of ours changes nothing', () => {
        const [element, events] = mount()

        element.value = '<p>From the server</p>'

        expect(element.value).toBe('<p>From the server</p>')
        expect(element.querySelector('input[type="hidden"]').value).toBe('<p>From the server</p>')
        expect(events).toEqual([])

        const doc = element.editor.state.doc

        element.value = '<p>From the server</p>'

        expect(element.editor.state.doc).toBe(doc)

        element.value = null

        expect(element.value).toBe('')
    })

    it('passes the field\'s error state on to the text box', () => {
        const [element] = mount()
        const input = element.querySelector('[data-slot="editor-input"]')

        element.setAttribute('aria-invalid', 'true')
        element.setAttribute('aria-describedby', 'body-error')

        expect(input.getAttribute('aria-invalid')).toBe('true')
        expect(input.getAttribute('aria-describedby')).toBe('body-error')
    })
})
