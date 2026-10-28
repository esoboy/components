import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import full from '../Fixtures/editor-full.json'

/*
 * ProseMirror measures the selection to scroll it into view; jsdom lays nothing out.
 */
beforeAll(async () => {
    const rect = () => ({ top: 0, left: 0, right: 0, bottom: 0, width: 0, height: 0, x: 0, y: 0 })

    // jsdom has no canvas to read color names with.
    HTMLCanvasElement.prototype.getContext = () => null
    Range.prototype.getClientRects = () => []
    Range.prototype.getBoundingClientRect = rect
    Element.prototype.getClientRects = () => []
    document.elementFromPoint = () => null

    await import('../../resources/js/heavy/editor.js')
})

afterEach(() => {
    document.body.innerHTML = ''
})

// What the view writes into data-format (RichTextFormat::client()): the full format's,
// kept in step with the server by EditorTest; a list of features narrows its whitelist.
const format = (features, allow = null) => ({
    ...full,
    features,
    allow: allow ?? (features.length === full.features.length ? full.allow : { p: [], br: [], div: [] }),
})

const COMMANDS = ['undo', 'redo', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'clear-formatting', 'indent', 'outdent', 'text-indent', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'horizontal-rule', 'table', 'details']
const TOGGLES = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'text-indent', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'details']

function mount(html = '<h2>Notes</h2><p>Hello world</p>', { features = full.features, tools = 'toolbar find-replace source fullscreen character-count markdown', attributes = '' } = {}) {
    document.body.innerHTML = `
        <form>
            <eso-editor id="body" data-slot="editor" data-format='${JSON.stringify(format(features)).replaceAll("'", '&#39;')}' data-tools="${tools}" data-placeholder="Write" data-label="Notes" data-name="body" ${attributes}>
                <div data-slot="editor-toolbar" role="toolbar">
                    ${COMMANDS.map(command => `<button type="button" data-editor-command="${command}" aria-label="${command}" ${TOGGLES.includes(command) ? 'aria-pressed="false"' : ''}></button>`).join('')}
                    <button type="button" data-editor-action="media" aria-label="Media"></button>
                    <button type="button" data-editor-action="find" aria-label="Find" aria-pressed="false"></button>
                    <button type="button" data-editor-action="source" aria-label="Source" aria-pressed="false"></button>
                    <button type="button" data-editor-action="fullscreen" aria-label="Full screen" aria-pressed="false"></button>
                    <button type="button" data-editor-action="format-painter" aria-label="Format painter" aria-pressed="false"></button>
                    <button type="button" data-editor-opens="link" commandfor="link" aria-pressed="false" aria-label="Link"></button>
                    <div id="link" popover="auto" data-editor-field-content>
                        <input type="url" data-editor-link-input>
                        <button type="button" data-editor-action="link-apply"></button>
                        <button type="button" data-editor-action="link-remove"></button>
                    </div>
                    <button type="button" data-editor-menu="heading" commandfor="headings" aria-label="Style"><svg data-editor-icon=""></svg><svg data-editor-icon="2" hidden></svg></button>
                    <div id="headings" popover="auto">
                        <button type="button" role="menuitemradio" aria-checked="false" data-editor-command="paragraph" data-editor-value=""></button>
                        <button type="button" role="menuitemradio" aria-checked="false" data-editor-command="heading" data-editor-value="2"></button>
                    </div>
                    <button type="button" data-editor-menu="font-size" commandfor="sizes" aria-label="Size"><span data-editor-current data-editor-default="Default size"></span></button>
                    <div id="sizes" popover="auto">
                        <button type="button" role="menuitemradio" aria-checked="false" data-editor-command="font-size" data-editor-value="" data-editor-label="Default size"></button>
                        <button type="button" role="menuitemradio" aria-checked="false" data-editor-command="font-size" data-editor-value="18px" data-editor-label="18"></button>
                    </div>
                    <div id="colors" popover="auto">
                        <button type="button" data-editor-command="color" data-editor-value="#dc2626" aria-pressed="false"></button>
                        <button type="button" data-editor-command="highlight" data-editor-value="#bbf7d0" aria-pressed="false"></button>
                    </div>
                </div>
                <div data-slot="editor-find" hidden>
                    <input type="search" data-editor-find-input>
                    <span data-editor-find-count></span>
                    <button type="button" data-editor-action="find-case" aria-pressed="false"></button>
                    <button type="button" data-editor-action="find-previous"></button>
                    <button type="button" data-editor-action="find-next"></button>
                    <input data-editor-replace-input>
                    <button type="button" data-editor-action="replace"></button>
                    <button type="button" data-editor-action="replace-all"></button>
                    <button type="button" data-editor-action="find-close"></button>
                </div>
                <div data-slot="editor-content">${html}</div>
                <textarea data-slot="editor-source" hidden></textarea>
                <div data-slot="editor-footer"><span data-editor-count></span></div>
                <template data-template="uploading"><span data-slot="editor-uploading"><span>Uploading…</span><button type="button" data-editor-action="dismiss-upload"></button></span></template>
                <div id="tip" popover="manual" data-slot="tooltip-content" data-editor-tip><span data-editor-tip-label></span><kbd data-editor-tip-keys hidden></kbd></div>
            </eso-editor>
        </form>`

    const element = document.querySelector('eso-editor')
    const events = []

    element.addEventListener('input', () => events.push('input'))
    element.addEventListener('change', () => events.push('change'))

    return [element, events]
}

const control = (element, selector) => element.querySelector(selector)

// A command's control, made for the test: clicked to run it, or asked whether it is on.
function commandControl(element, command, value) {
    const button = Object.assign(document.createElement('button'), { type: 'button' })

    button.dataset.editorCommand = command
    button.dataset.editorValue = value
    button.setAttribute('aria-pressed', 'false')
    element.querySelector('[data-slot="editor-toolbar"]').append(button)

    return button
}

const run = (element, command, value = '') => commandControl(element, command, value).click()

function active(element, command, value = '') {
    const button = commandControl(element, command, value)

    element.editor.view.dispatch(element.editor.state.tr)

    return button.getAttribute('aria-pressed') === 'true'
}
const button = (element, command) => control(element, `[data-editor-command="${command}"]`)
const select = (element, text) => {
    let from = 0

    element.editor.state.doc.descendants((node, position) => {
        if (node.isText && node.text.includes(text)) from = position + node.text.indexOf(text)
    })
    element.editor.commands.setTextSelection({ from, to: from + text.length })
}

// Pastes clipboard data as the browser would.
function paste(element, data) {
    const clipboardData = { getData: type => data[type] ?? '', files: data.files ?? [], types: Object.keys(data) }
    const event = new Event('paste', { bubbles: true, cancelable: true })

    Object.defineProperty(event, 'clipboardData', { value: clipboardData })
    element.editor.view.dom.dispatchEvent(event)
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

    it('makes its toolbar one tab stop, with tooltips and shortcuts', () => {
        const [element] = mount()
        const undo = button(element, 'undo')
        const bold = button(element, 'bold')

        expect([...element.querySelectorAll('[data-slot="editor-toolbar"] > button')].filter(control => control.tabIndex === 0)).toEqual([bold])
        expect(bold.dataset.tooltip).toBe('tip')
        expect(undo.disabled).toBe(true)

        bold.dataset.editorKeys = 'Mod-b'
        bold.dispatchEvent(new Event('pointerover', { bubbles: true }))

        expect(element.querySelector('[data-editor-tip-label]').textContent).toBe('bold')
        expect(element.querySelector('[data-editor-tip-keys]').textContent).toBe('Ctrl+B')
        expect(element.querySelector('[data-editor-tip-keys]').hidden).toBe(false)

        bold.focus()
        bold.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }))

        expect(document.activeElement).toBe(button(element, 'italic'))
        expect(bold.tabIndex).toBe(-1)
        expect(button(element, 'italic').tabIndex).toBe(0)
    })
})

describe('formats', () => {
    it('writes only what its format has, whatever it is given', () => {
        const [text] = mount('<h2>Title</h2><p><strong>Bold</strong> <a href="https://x.test">link</a></p><ul><li><p>Item</p></li></ul>', { features: [] })

        expect(text.value).toBe('<p>Title</p><p>Bold link</p><p>Item</p>')

        const [comment] = mount('<h2>Title</h2><p><strong>Bold</strong> <em>i</em> <span style="color: #dc2626">red</span></p>', { features: ['bold'] })

        expect(comment.value).toBe('<p>Title</p><p><strong>Bold</strong> i red</p>')
    })
})

describe('commands', () => {
    it('toggles marks and blocks, and shows what is on', () => {
        const [element, events] = mount()

        select(element, 'world')
        button(element, 'bold').click()
        button(element, 'superscript').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello <strong><sup>world</sup></strong></p>')
        expect(button(element, 'bold').getAttribute('aria-pressed')).toBe('true')
        expect(events).toContain('input')

        button(element, 'task-list').click()

        expect(element.value).toContain('<ul style="list-style-type: none; padding-left: 0px;"><li style="display: flex; align-items: baseline; gap: 0.5em;"><input type="checkbox" disabled="disabled"><div><p>Hello')
    })

    it('checks the current value in its menus, and shows it on their button', () => {
        const [element] = mount()
        const style = control(element, '[data-editor-menu="heading"]')

        select(element, 'Notes')

        expect(control(element, '[data-editor-command="heading"]').getAttribute('aria-checked')).toBe('true')
        expect(style.querySelector('[data-editor-icon="2"]').hasAttribute('hidden')).toBe(false)
        expect(style.querySelector('[data-editor-icon=""]').hasAttribute('hidden')).toBe(true)

        select(element, 'world')
        control(element, '[data-editor-command="font-size"][data-editor-value="18px"]').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello <span style="font-size: 18px;">world</span></p>')
        expect(control(element, '[data-editor-current]').textContent).toBe('18')
    })
})

describe('styles', () => {
    it('writes colors, highlights, spacing and text layout as inline style', () => {
        const [element] = mount('<p>Hello world</p>')

        select(element, 'Hello')
        control(element, '[data-editor-command="color"]').click()
        select(element, 'world')
        control(element, '[data-editor-command="highlight"]').click()
        element.editor.chain().setBlockStyle('text-align', 'center').setBlockStyle('line-height', '1.75').setBlockStyle('margin-bottom', '10px').setBlockStyle('letter-spacing', '1px').indent().indent().toggleTextIndent().run()

        expect(element.value).toBe('<p style="text-align: center; line-height: 1.75; margin-bottom: 10px; letter-spacing: 1px; margin-left: 4em; text-indent: 2em;"><span style="color: rgb(220, 38, 38);">Hello</span> <mark style="background-color: rgb(187, 247, 208); color: rgb(23, 23, 23);">world</mark></p>')
        expect(active(element, 'text-align', 'center')).toBe(true)

        element.editor.chain().selectAll().clearFormatting().run()

        expect(element.value).toBe('<p>Hello world</p>')
    })

    it('changes one property of a style, keeping the others', () => {
        const [element] = mount('<p><span style="font-size: 20px; color: rgb(255, 0, 0);">Hello</span> world</p>')

        element.editor.commands.setTextSelection({ from: 1, to: 12 })
        control(element, '[data-editor-command="color"]').click()

        expect(element.value).toBe('<p><span style="font-size: 20px; color: rgb(220, 38, 38);">Hello</span><span style="color: rgb(220, 38, 38);"> world</span></p>')
    })

    it('keeps the whitelisted CSS of what it is given, as written, and nothing else', () => {
        const [element] = mount('<p style="text-align: right; position: fixed; line-height: 2;"><span style="color: #111; font-size: 10.5pt; font-family: Songti SC, SimSun; z-index: 9; background: url(javascript:x)">a</span> <span style="background-color: yellow">b</span> <span data-x="1">c</span></p>')

        expect(element.value).toBe('<p style="text-align: right; line-height: 2;"><span style="color: rgb(17, 17, 17); font-size: 10.5pt; font-family: Songti SC, SimSun;">a</span> <span style="background-color: yellow;">b</span> c</p>')
    })

    it('keeps tags no feature writes when the whitelist has them, as they are', () => {
        const [element] = mount('<section style="padding: 8px; position: fixed;"><p>One</p><section style="color: red;">Two <small>small</small> <abbr title="Laravel">LV</abbr></section></section><div>Three</div><marquee>Four</marquee>')

        expect(element.value).toBe('<section style="padding: 8px;"><p>One</p><section style="color: red;">Two <small>small</small> <abbr title="Laravel">LV</abbr></section></section><div>Three</div><p>Four</p>')
        expect(element.editor.getJSON().content.map(node => node.type)).toEqual(['container', 'textContainer', 'paragraph'])
    })

    it('makes lists pasted from Word and WPS lists', async () => {
        const { wordLists } = await import('../../resources/js/heavy/editor/paste.js')
        const word = '<p class="MsoListParagraph" style="mso-list:l0 level1 lfo1"><!--[if !supportLists]--><span style="mso-list:Ignore">1.<span>&nbsp;</span></span><!--[endif]-->One</p>'
            + '<p class="MsoListParagraph" style="mso-list:l0 level2 lfo1"><span style="font-family:Symbol"><span style="mso-list:Ignore">·</span></span>Inner</p>'
            + '<p class="MsoListParagraph" style="mso-list:l0 level1 lfo1"><span style="mso-list:Ignore">2.</span>Two</p><p>After</p>'

        expect(wordLists(word).replace(/<!--.*?-->/g, '')).toBe('<ol><li><p style="mso-list:l0 level1 lfo1">One</p><ul><li><p style="mso-list:l0 level2 lfo1">Inner</p></li></ul></li><li><p style="mso-list:l0 level1 lfo1">Two</p></li></ol><p>After</p>')
        expect(wordLists('<p>Plain</p>')).toBe('<p>Plain</p>')
    })
})

describe('blocks', () => {
    it('reads task lists from boxes, as Markdown, GitHub and Tiptap write them', () => {
        const [element] = mount('<ul><li><input type="checkbox" checked> Done</li><li><label><input type="checkbox"></label><div><p>Todo</p></div></li></ul><ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><input type="checkbox" checked><span></span></label><div><p>Tiptap</p></div></li></ul><ul><li><p>Plain</p></li></ul>')
        const tasks = element.editor.getJSON().content

        expect(tasks.map(node => node.type)).toEqual(['taskList', 'taskList', 'bulletList'])
        expect(tasks[0].content.map(item => [item.attrs.checked, item.content[0].content[0].text])).toEqual([[true, 'Done'], [false, 'Todo']])
        expect(tasks[1].content[0].attrs.checked).toBe(true)
    })

    it('keeps a paragraph after a last table or image to write in, out of its value', () => {
        const [element, events] = mount('<p>Intro</p><img src="/a.png">')

        element.editor.commands.setTextSelection(1)

        expect(element.editor.state.doc.lastChild.type.name).toBe('paragraph')
        expect(element.value).toBe('<p>Intro</p><img src="/a.png">')
        expect(events).toEqual([])

        element.value = '<p>Intro</p><img src="/a.png">'

        expect(element.editor.state.selection.from).toBe(1)
    })

    it('writes six levels of headings, and indents code with Tab', () => {
        const [element] = mount('<h5>Five</h5><h6>Six</h6><pre><code>a</code></pre>')

        expect(element.value).toBe('<h5>Five</h5><h6>Six</h6><pre><code>a</code></pre>')

        element.editor.commands.setTextSelection(element.editor.state.doc.content.size - 2)
        element.editor.view.dom.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }))

        expect(element.value).toContain('<pre><code>    a</code></pre>')
    })

    it('resizes media by their handles, as a width in px or the full width', () => {
        const [element] = mount('<img src="/a.png" style="width: 200px; margin-left: auto; margin-right: auto;">')
        const media = element.querySelector('[data-editor-media="image"]')
        const box = media.querySelector('[data-editor-media-box]')
        const right = media.querySelector('[data-editor-resize="right"]')
        const drag = (handle, to) => {
            handle.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, cancelable: true, clientX: 100 }))
            handle.dispatchEvent(new MouseEvent('pointermove', { bubbles: true, clientX: to }))
            handle.dispatchEvent(new MouseEvent('pointerup', { bubbles: true, clientX: to }))
        }

        Object.defineProperty(media, 'clientWidth', { value: 600 })
        box.getBoundingClientRect = () => ({ width: 200 })

        expect(media.dataset.align).toBe('center')
        expect(box.style.width).toBe('200px')

        drag(right, 150)

        expect(element.value).toBe('<img style="width: 300px; margin-left: auto; margin-right: auto;" src="/a.png">')

        box.getBoundingClientRect = () => ({ width: 300 })
        drag(right, 400)

        expect(element.value).toContain('width: 100%;')
    })

    it('writes collapsible blocks as native details, and code blocks without a language', () => {
        const [element] = mount('<details open><summary>More</summary><div><p>Hidden</p></div></details><pre><code class="language-php">echo 1;</code></pre>')

        expect(element.value).toBe('<details><summary>More</summary><div><p>Hidden</p></div></details><pre><code>echo 1;</code></pre>')
    })

    it('writes tables with inline borders and column widths', () => {
        const [element] = mount('<table><colgroup><col style="width: 120px"><col></colgroup><tbody><tr><th><p>A</p></th><th><p>B</p></th></tr><tr><td><p>1</p></td><td><p>2</p></td></tr></tbody></table>')

        expect(element.value).toBe('<div style="overflow-x: auto;"><table style="border-collapse: collapse; width: 100%;"><colgroup><col style="width: 120px;"><col style="min-width: 48px;"></colgroup><tbody><tr>'
            + '<th style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top; background-color: rgba(128, 128, 128, 0.1); text-align: left;" colwidth="120"><p>A</p></th>'.replace(' colwidth="120"', '')
            + '<th style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top; background-color: rgba(128, 128, 128, 0.1); text-align: left;"><p>B</p></th></tr><tr>'
            + '<td style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top;"><p>1</p></td>'
            + '<td style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top;"><p>2</p></td></tr></tbody></table></div>')
        expect(element.editor.getJSON().content[0].content[0].content[0].attrs.colwidth).toEqual([120])
    })

    it('keeps media on web addresses with their style, and no data: images', () => {
        const [element] = mount('<img src="/a.png" alt="A" width="1200" height="800" style="width: 50%; margin-left: auto; border-radius: 8px;"><img src="data:image/png;base64,AAAA"><video src="https://cdn.test/v.mp4" poster="/p.jpg"></video><audio src="/a.mp3"></audio><a href="/f.pdf" download="f.pdf" type="application/pdf"><span>📎</span><span>f.pdf</span><span>1 KB</span></a><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe><iframe src="https://evil.test/x"></iframe>')

        expect(element.value).toBe('<img style="width: 50%; margin-left: auto; border-radius: 8px;" src="/a.png" alt="A" width="1200" height="800">'
            + '<video src="https://cdn.test/v.mp4" poster="/p.jpg" controls="controls" preload="metadata"></video>'
            + '<audio src="/a.mp3" controls="controls" preload="metadata"></audio>'
            + '<a href="/f.pdf" download="f.pdf" type="application/pdf" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 8px; color: inherit; text-decoration: none;"><span>📎</span><span>f.pdf</span><span style="opacity: 0.6;">1 KB</span></a>'
            + '<iframe style="display: block; width: 100%; max-width: 100%; height: auto; aspect-ratio: 16 / 9; border: 0px;" src="https://www.youtube-nocookie.com/embed/abc" allowfullscreen="allowfullscreen" loading="lazy"></iframe>')
    })

    it('captions images, links them, and wraps blocks in callouts', () => {
        const [element] = mount('<figure><a href="https://x.test"><img src="/a.png"></a><figcaption>Cap</figcaption></figure><img src="/b.png"><p>Note</p>')
        let image = null

        element.editor.state.doc.descendants((node, position) => {
            if (node.attrs.src === '/b.png') image = position
        })
        element.editor.chain().setNodeSelection(image).toggleCaption().run()

        expect(element.value).toBe('<figure style="margin: 0px;"><a href="https://x.test"><img src="/a.png"></a><figcaption style="margin-top: 6px; text-align: center; font-size: 0.875em; color: rgb(107, 114, 128);">Cap</figcaption></figure>'
            + '<figure style="margin: 0px;"><img src="/b.png"><figcaption style="margin-top: 6px; text-align: center; font-size: 0.875em; color: rgb(107, 114, 128);"></figcaption></figure><p>Note</p>')

        element.editor.chain().setTextSelection(element.editor.state.doc.content.size - 2).setCallout(full.callouts.info).run()

        expect(element.value).toContain('<aside style="padding: 12px 16px; border-radius: 6px; background-color: rgba(59, 130, 246, 0.1); border-left: 4px solid rgb(59, 130, 246);"><p>Note</p></aside>')
        expect(active(element, 'callout', full.callouts.info)).toBe(true)
    })

    it('colors and aligns table cells', () => {
        const [element] = mount('<table><tbody><tr><td><p>1</p></td><td><p>2</p></td></tr></tbody></table>')

        element.editor.commands.setTextSelection(3)
        run(element, 'cell-background', '#fef08a')
        run(element, 'cell-align', 'middle')

        expect(element.value).toContain('<td style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: middle; background-color: rgb(254, 240, 138);"><p>1</p></td><td style="border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top;"><p>2</p></td>')
        expect(active(element, 'cell-align', 'middle')).toBe(true)
    })

    it('keeps formulas as MathML with their LaTeX, written by KaTeX', async () => {
        const { toMathML } = await import('../../resources/js/heavy/editor/nodes.js')
        const mathml = await toMathML('x^2', false)
        const [element] = mount(`<p>Area ${mathml}</p>`)

        expect(mathml).toMatch(/^<math xmlns="http:\/\/www\.w3\.org\/1998\/Math\/MathML"><semantics><mrow><msup><mi>x<\/mi><mn>2<\/mn><\/msup><\/mrow><annotation encoding="application\/x-tex">x\^2<\/annotation><\/semantics><\/math>$/)
        expect(element.editor.getJSON().content[0].content[1]).toMatchObject({ type: 'inlineMath', attrs: { latex: 'x^2' } })
        expect(element.value).toBe(`<p>Area ${mathml}</p>`)
        await expect(toMathML('\\frac{', true)).rejects.toThrow('KaTeX parse error')
    })
})

describe('media', () => {
    it('asks for media with editor-media, and inserts the answer where it was asked', () => {
        const [element] = mount('<p>Hello world</p>')
        const requests = []

        element.addEventListener('editor-media', event => requests.push(event.detail))
        select(element, 'Hello')
        control(element, '[data-editor-action="media"]').click()
        element.editor.chain().setTextSelection(12).insertContent('!').run()

        expect(requests).toHaveLength(1)
        expect(requests[0].files).toEqual([])
        expect(requests[0].accept).toEqual(['image', 'audio', 'video', 'file'])

        requests[0].insert([
            { url: '/a.png', name: 'a.png', mime: 'image/png', alt: 'A', width: 10, height: 20 },
            { url: '/b.mp3', name: 'b.mp3' },
            { url: '/c.zip', name: 'c.zip', size: 1536 },
            { url: 'javascript:alert(1)', name: 'x.png' },
        ])

        expect(element.value).toBe('<img style="display: block; max-width: 100%; height: auto; margin-left: auto; margin-right: auto;" src="/a.png" alt="A" width="10" height="20">'
            + '<audio style="display: block; width: 100%;" src="/b.mp3" title="b.mp3" controls="controls" preload="metadata"></audio>'
            + '<a href="/c.zip" download="c.zip" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 8px; color: inherit; text-decoration: none;"><span>📎</span><span>c.zip</span><span style="opacity: 0.6;">1.5 KB</span></a>'
            + '<p> world!</p>')
    })

    it('inserts what the server answers, and nothing for none', () => {
        const [element] = mount('<p>Hello</p>', { features: ['file'] })

        element.addEventListener('editor-media', () => {})
        element.editor.commands.setTextSelection(6)
        control(element, '[data-editor-action="media"]').click()
        window.dispatchEvent(new CustomEvent('ui-editor', { detail: { id: 'body', action: 'insert', attachments: [], html: null } }))

        expect(element.value).toBe('<p>Hello</p>')

        window.dispatchEvent(new CustomEvent('ui-editor', { detail: { id: 'other', action: 'insert', attachments: [{ url: '/x.png' }], html: null } }))
        window.dispatchEvent(new CustomEvent('ui-editor', { detail: { id: 'body', action: 'insert', attachments: [{ url: '/x.png', mime: 'image/png' }], html: null } }))

        expect(element.value).toBe('<p>Hello</p><a href="/x.png" download="x.png" type="image/png" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 8px; color: inherit; text-decoration: none;"><span>📎</span><span>x.png</span></a>')

        window.dispatchEvent(new CustomEvent('ui-editor', { detail: { id: 'body', action: 'insert-content', attachments: [], html: '<p><strong>Hi</strong></p>' } }))

        expect(element.value).toContain('<p>Hi</p>')
    })

    it('hands pasted files to the app, and turns pasted links into what they are', () => {
        const [element] = mount('<p>Hello</p>')
        const requests = []
        const file = new File(['x'], 'shot.png', { type: 'image/png' })

        element.addEventListener('editor-media', event => requests.push(event.detail))
        element.editor.commands.setTextSelection(6)
        paste(element, { files: [file] })

        expect(requests.map(request => request.files)).toEqual([[file]])

        paste(element, { 'text/plain': '## Heading\n\n- [x] Done\n\n**bold**' })
        paste(element, { 'text/plain': 'https://youtu.be/dQw4w9WgXcQ' })

        expect(element.value).toContain('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"')
        expect(element.value).toContain('<h2>Heading</h2><ul style="list-style-type: none; padding-left: 0px;"><li style="display: flex; align-items: baseline; gap: 0.5em;"><input type="checkbox" disabled="disabled" checked="checked"><div><p>Done</p></div></li></ul><p><strong>bold</strong></p>')
    })

    it('shows where pasted files will go until the app answers, or they are dismissed', () => {
        const [element] = mount('<p>Hello</p>')
        const requests = []

        element.addEventListener('editor-media', event => requests.push(event.detail))
        element.editor.commands.setTextSelection(6)
        paste(element, { files: [new File(['x'], 'a.png', { type: 'image/png' })] })
        paste(element, { files: [new File(['y'], 'b.png', { type: 'image/png' })] })

        expect(element.querySelectorAll('[data-slot="editor-uploading"]')).toHaveLength(2)
        expect(element.value).toBe('<p>Hello</p>')

        requests[0].insert([{ url: '/a.png', mime: 'image/png' }])

        expect(element.querySelectorAll('[data-slot="editor-uploading"]')).toHaveLength(1)
        expect(element.value).toContain('src="/a.png"')

        element.querySelector('[data-slot="editor-uploading"] [data-editor-action="dismiss-upload"]').click()

        expect(element.querySelectorAll('[data-slot="editor-uploading"]')).toHaveLength(0)
    })

    it('asks the app to import images from elsewhere, and puts its copies in their place', () => {
        const [element] = mount('<p>Hello</p>', { attributes: 'data-image-hosts="cdn.app.test"' })
        const requests = []

        element.addEventListener('editor-external-images', event => requests.push(event.detail))
        element.editor.commands.setTextSelection(6)
        paste(element, { 'text/html': '<p><img src="https://other.test/a.png"><img src="https://other.test/a.png"><img src="https://cdn.app.test/b.png"><img src="/c.png"><img src="https://other.test/d.png"></p>' })

        return new Promise(resolve => queueMicrotask(resolve)).then(() => {
            expect(requests.map(request => request.urls)).toEqual([['https://other.test/a.png', 'https://other.test/d.png']])
            expect(element.querySelectorAll('[data-editor-importing]')).toHaveLength(3)

            requests[0].replace([{ url: '/uploads/a.png', name: 'a.png', mime: 'image/png' }, null])

            expect(element.value.match(/src="[^"]+"/g)).toEqual(['src="/uploads/a.png"', 'src="/uploads/a.png"', 'src="https://cdn.app.test/b.png"', 'src="/c.png"', 'src="https://other.test/d.png"'])
            expect(element.querySelectorAll('[data-editor-importing]')).toHaveLength(0)

            window.dispatchEvent(new CustomEvent('ui-editor', { detail: { id: 'body', action: 'replace-images', attachments: [{ url: '/x.png' }], html: null } }))

            expect(element.value).toContain('src="https://other.test/d.png"')
        })
    })

    it('reads attachments and embed links', async () => {
        const { attachmentNode, embedSource, formatSize, kindOf } = await import('../../resources/js/heavy/editor/media.js')
        const platforms = format([]).platforms

        expect([{ mime: 'image/webp' }, { type: 'video/mp4' }, { url: '/a/b.MP3?x=1' }, { url: '/a.pdf' }, { mime: 'application/zip', url: '/a.png' }].map(kindOf)).toEqual(['image', 'video', 'audio', 'file', 'file'])
        expect([0, 1023, 1536, 5 * 1024 ** 2].map(formatSize)).toEqual(['0 B', '1,023 B', '1.5 KB', '5 MB'])
        expect(attachmentNode({ url: '/v.mp4', mime: 'video/mp4' }, ['image', 'file'])).toMatchObject({ type: 'file', attrs: { name: 'v.mp4' } })
        expect(attachmentNode({ url: '/v.mp4', mime: 'video/mp4' }, ['image'])).toBeNull()
        expect(attachmentNode({ url: 'data:image/png;base64,AA', mime: 'image/png' }, ['image'])).toBeNull()
        expect(embedSource('https://www.youtube.com/watch?t=1&v=dQw4w9WgXcQ', platforms)).toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        expect(embedSource('https://www.bilibili.com/video/BV1GJ411x7h7?spm=1', platforms)).toBe('https://player.bilibili.com/player.html?bvid=BV1GJ411x7h7&autoplay=0')
        expect(embedSource('<iframe src="https://player.bilibili.com/player.html?bvid=BV1&amp;page=1" allowfullscreen></iframe>', platforms)).toBe('https://player.bilibili.com/player.html?bvid=BV1&page=1')
        expect(embedSource('https://evil.test/x', platforms)).toBeNull()
    })
})

describe('format painter', () => {
    it('puts the marks and block style it picked up on the next text selected, once', () => {
        const [element] = mount('<p style="text-align: center;"><strong><span style="color: rgb(220, 38, 38);">Bold</span></strong> <a href="https://x.test">plain</a></p><p>Other text</p>')
        const painter = control(element, '[data-editor-action="format-painter"]')

        element.editor.commands.setTextSelection(2)
        painter.click()

        expect(painter.getAttribute('aria-pressed')).toBe('true')
        expect(element.hasAttribute('data-painting')).toBe(true)

        select(element, 'Other')
        element.editor.view.dom.dispatchEvent(new Event('pointerup', { bubbles: true }))

        expect(element.value).toBe('<p style="text-align: center;"><span style="color: rgb(220, 38, 38);"><strong>Bold</strong></span> <a target="_blank" rel="noopener noreferrer nofollow" href="https://x.test">plain</a></p><p style="text-align: center;"><span style="color: rgb(220, 38, 38);"><strong>Other</strong></span> text</p>')
        expect(painter.getAttribute('aria-pressed')).toBe('false')
        expect(element.hasAttribute('data-painting')).toBe(false)
    })
})

describe('find and replace', () => {
    it('finds matches, moves between them and replaces them', () => {
        const [element] = mount('<p>Cat cat CAT dog</p>')
        const find = control(element, '[data-editor-find-input]')
        const count = control(element, '[data-editor-find-count]')

        control(element, '[data-editor-action="find"]').click()

        expect(control(element, '[data-slot="editor-find"]').hidden).toBe(false)
        expect(document.activeElement).toBe(find)

        find.value = 'cat'
        find.dispatchEvent(new Event('input', { bubbles: true }))

        expect(count.textContent).toBe('1/3')
        expect(element.querySelectorAll('[data-editor-match]')).toHaveLength(3)

        find.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }))

        expect(count.textContent).toBe('2/3')

        control(element, '[data-editor-action="find-case"]').click()

        expect(count.textContent).toBe('1/1')

        control(element, '[data-editor-replace-input]').value = 'bird'
        control(element, '[data-editor-action="replace-all"]').click()

        expect(element.value).toBe('<p>Cat bird CAT dog</p>')
        expect(count.textContent).toBe('0/0')

        find.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))

        expect(control(element, '[data-slot="editor-find"]').hidden).toBe(true)
        expect(element.querySelectorAll('[data-editor-match]')).toHaveLength(0)
    })
})

describe('views', () => {
    it('shows and edits its HTML source, as far as its format allows', () => {
        const [element, events] = mount('<h2>Notes</h2><p>Hello</p>')
        const source = control(element, '[data-slot="editor-source"]')

        control(element, '[data-editor-action="source"]').click()

        expect(source.hidden).toBe(false)
        expect(source.value).toBe('<h2>Notes</h2>\n<p>Hello</p>')
        expect(element.hasAttribute('data-source')).toBe(true)
        expect(button(element, 'bold').disabled).toBe(true)

        source.value = '<h2>Notes</h2><p>Hi <b>there</b></p><script>alert(1)</script>'
        source.dispatchEvent(new Event('input', { bubbles: true }))

        expect(element.value).toBe('<h2>Notes</h2><p>Hi <strong>there</strong></p>')
        expect(events).toContain('input')

        control(element, '[data-editor-action="source"]').click()

        expect(source.hidden).toBe(true)
        expect(element.hasAttribute('data-source')).toBe(false)
    })

    it('goes full screen, and back with Escape', () => {
        const [element] = mount()
        const toggle = control(element, '[data-editor-action="fullscreen"]')

        toggle.click()

        expect(element.hasAttribute('data-fullscreen')).toBe(true)
        expect(toggle.getAttribute('aria-pressed')).toBe('true')
        expect(document.documentElement.style.overflow).toBe('hidden')

        element.editor.view.dom.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))

        expect(element.hasAttribute('data-fullscreen')).toBe(false)
        expect(document.documentElement.style.overflow).toBe('')
    })

    it('counts characters as graphemes', () => {
        const [element] = mount('<p>汉字 👍🏽</p>')

        expect(control(element, '[data-editor-count]').textContent).toBe('4')

        const [limited] = mount('<p>abc</p>', { attributes: 'data-max-length="3"' })

        expect(control(limited, '[data-editor-count]').textContent).toBe('3 / 3')
        expect(control(limited, '[data-editor-count]').hasAttribute('data-full')).toBe(true)
    })
})

describe('links', () => {
    it('opens its link popover with Mod-K', () => {
        const [element] = mount()
        const opened = vi.fn()

        control(element, '[data-editor-opens="link"]').addEventListener('click', opened)
        select(element, 'world')
        element.editor.view.dom.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true, cancelable: true }))

        expect(opened).toHaveBeenCalledOnce()
    })

    it('sets and removes the selection link from its popover, Enter applying it in place', () => {
        const [element] = mount()
        const input = control(element, '[data-editor-link-input]')
        const form = element.closest('form')
        const submitted = vi.fn(event => event.preventDefault())

        form.addEventListener('submit', submitted)
        select(element, 'world')

        expect(control(element, '[data-editor-opens="link"]').disabled).toBe(false)

        input.value = 'https://laravel.com'
        input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }))

        expect(element.value).toBe('<h2>Notes</h2><p>Hello <a target="_blank" rel="noopener noreferrer nofollow" href="https://laravel.com">world</a></p>')
        expect(submitted).not.toHaveBeenCalled()

        control(element, '[data-editor-action="link-remove"]').click()

        expect(element.value).toBe('<h2>Notes</h2><p>Hello world</p>')
    })
})

describe('the value', () => {
    it('takes the server HTML without reporting it back, and leaves the same content be', () => {
        const [element, events] = mount('<p style="text-align: center;"><span style="color: rgb(220, 38, 38);">Hello</span></p>')

        element.editor.commands.setTextSelection(3)
        element.value = '<p style="text-align:center"><span style="color:#dc2626">Hello</span></p>'

        expect(element.editor.state.selection.from).toBe(3)

        element.value = '<p>New</p>'

        expect(element.value).toBe('<p>New</p>')
        expect(element.querySelector('input[name="body"]').value).toBe('<p>New</p>')
        expect(events).toEqual([])
    })

    it('reports change once editing ends, if it changed anything', () => {
        const [element, events] = mount()

        element.editor.view.dom.dispatchEvent(new FocusEvent('focus'))
        element.editor.commands.insertContent('!')
        element.editor.view.dom.dispatchEvent(new FocusEvent('focusout', { bubbles: true, relatedTarget: document.body }))
        element.editor.commands.blur()

        expect(events.filter(event => event === 'change')).toHaveLength(1)
    })
})
