import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'

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

const PRESETS = {
    text: [],
    basic: ['heading', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'link', 'color', 'highlight', 'font-family', 'font-size', 'text-align', 'indent', 'text-indent', 'line-height', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'horizontal-rule', 'emoji'],
}

PRESETS.full = [...PRESETS.basic, 'code-block', 'image', 'audio', 'video', 'file', 'embed', 'table', 'details', 'math']

// What the view writes into data-format (RichTextFormat::client()).
const format = features => ({
    features,
    headings: [1, 2, 3, 4],
    colors: ['#6b7280', '#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#3b82f6', '#a855f7', '#db2777'],
    highlights: ['#fef08a', '#bbf7d0', '#bfdbfe', '#e9d5ff', '#fbcfe8', '#fecaca', '#fed7aa', '#e5e7eb'],
    highlightText: '#171717',
    fonts: ['sans-serif', 'serif', 'monospace', 'Arial, sans-serif', 'Georgia, serif', "'Times New Roman', serif", "'Courier New', monospace", "'Microsoft YaHei', 'PingFang SC', sans-serif", "SimSun, 'Songti SC', serif", "SimHei, 'Heiti SC', sans-serif", "KaiTi, 'Kaiti SC', serif", 'FangSong, STFangsong, serif'],
    sizes: [12, 13, 14, 15, 16, 18, 20, 24, 28, 32, 36, 48],
    lineHeights: ['1', '1.15', '1.5', '1.75', '2', '2.5', '3'],
    indents: 8,
    platforms: [['^https?://(?:www\\.|m\\.)?(?:youtube\\.com/(?:watch\\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\\.be/)([\\w-]{11})', 'https://www.youtube-nocookie.com/embed/$1'], ['^https?://(?:www\\.|m\\.)?bilibili\\.com/video/(BV\\w{10})', 'https://player.bilibili.com/player.html?bvid=$1&autoplay=0']],
    styles: { cell: 'border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top', headerCell: 'background-color:rgba(128,128,128,0.1);text-align:left', card: 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(128,128,128,0.4);border-radius:8px;color:inherit;text-decoration:none' },
})

const COMMANDS = ['undo', 'redo', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'clear-formatting', 'indent', 'outdent', 'text-indent', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'horizontal-rule', 'table', 'details']
const TOGGLES = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'text-indent', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'details']

function mount(html = '<h2>Notes</h2><p>Hello world</p>', { features = PRESETS.full, tools = 'toolbar find-replace source fullscreen character-count markdown', attributes = '' } = {}) {
    document.body.innerHTML = `
        <form>
            <eso-editor id="body" data-slot="editor" data-format='${JSON.stringify(format(features)).replaceAll("'", '&#39;')}' data-tools="${tools}" data-placeholder="Write" data-label="Notes" data-name="body" ${attributes}>
                <div data-slot="editor-toolbar" role="toolbar">
                    ${COMMANDS.map(command => `<button type="button" data-editor-command="${command}" aria-label="${command}" ${TOGGLES.includes(command) ? 'aria-pressed="false"' : ''}></button>`).join('')}
                    <button type="button" data-editor-action="media" aria-label="Media"></button>
                    <button type="button" data-editor-action="find" aria-label="Find" aria-pressed="false"></button>
                    <button type="button" data-editor-action="source" aria-label="Source" aria-pressed="false"></button>
                    <button type="button" data-editor-action="fullscreen" aria-label="Full screen" aria-pressed="false"></button>
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
    it('writes colors, highlights and text layout as inline style', () => {
        const [element] = mount('<p>Hello world</p>')

        select(element, 'Hello')
        control(element, '[data-editor-command="color"]').click()
        select(element, 'world')
        control(element, '[data-editor-command="highlight"]').click()
        element.editor.chain().setTextAlign('center').setLineHeight('1.75').indent().indent().toggleTextIndent().run()

        expect(element.value).toBe('<p style="text-align: center; margin-left: 4em; line-height: 1.75; text-indent: 2em;"><span style="color: rgb(220, 38, 38);">Hello</span> <mark style="background-color: rgb(187, 247, 208); color: rgb(23, 23, 23);">world</mark></p>')

        element.editor.chain().selectAll().clearFormatting().run()

        expect(element.value).toBe('<p>Hello world</p>')
    })

    it('reads its own styles back, and snaps pasted ones to the nearest it offers', () => {
        const [element] = mount('<p style="text-align: right; margin-left: 2em; line-height: 2;"><span style="color: rgb(220, 38, 38); font-size: 10.5pt; font-family: Songti SC, SimSun;">a</span> <span style="color: #111; font-family: Inter, sans-serif;">b</span> <span style="background-color: rgb(255, 255, 0)">c</span> <span style="color: #ff5555">d</span> <span style="background-color: #fff">e</span></p>')

        expect(element.value).toBe('<p style="text-align: right; margin-left: 2em; line-height: 2;"><span style="color: rgb(220, 38, 38); font-family: SimSun, &quot;Songti SC&quot;, serif; font-size: 14px;">a</span> b <mark style="background-color: rgb(254, 240, 138); color: rgb(23, 23, 23);">c</mark> <span style="color: rgb(220, 38, 38);">d</span> e</p>')
    })

    it('drops a pasted size of body text, but keeps one copied within an editor', async () => {
        const { cleanPastedHTML } = await import('../../resources/js/heavy/editor/styles.js')

        expect(cleanPastedHTML('<p><span style="font-size: 16px; color: red">a</span><span style="font-size: 24px">b</span></p>')).toBe('<p><span style="color: red;">a</span><span style="font-size: 24px">b</span></p>')
        expect(cleanPastedHTML('<p data-pm-slice="1 1 []"><span style="font-size: 16px">a</span></p>')).toBe('<p data-pm-slice="1 1 []"><span style="font-size: 16px">a</span></p>')
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

    it('keeps media on web addresses, sized and aligned inline, and no data: images', () => {
        const [element] = mount('<img src="/a.png" alt="A" width="1200" height="800" style="width: 50%; margin-left: auto;"><img src="data:image/png;base64,AAAA"><video src="https://cdn.test/v.mp4" poster="/p.jpg"></video><audio src="/a.mp3"></audio><a href="/f.pdf" download="f.pdf" type="application/pdf"><span>📎</span><span>f.pdf</span><span>1 KB</span></a><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe><iframe src="https://evil.test/x"></iframe>')

        expect(element.value).toBe('<img src="/a.png" alt="A" width="1200" height="800" style="display: block; width: 50%; max-width: 100%; height: auto; margin-left: auto;">'
            + '<video src="https://cdn.test/v.mp4" poster="/p.jpg" controls="controls" preload="metadata" style="display: block; max-width: 100%; height: auto;"></video>'
            + '<audio src="/a.mp3" controls="controls" preload="metadata" style="display: block; width: 100%;"></audio>'
            + '<a href="/f.pdf" download="f.pdf" type="application/pdf" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 8px; color: inherit; text-decoration: none;"><span>📎</span><span>f.pdf</span><span style="opacity: 0.6;">1 KB</span></a>'
            + '<iframe src="https://www.youtube-nocookie.com/embed/abc" allowfullscreen="allowfullscreen" loading="lazy" style="display: block; width: 100%; max-width: 100%; height: auto; aspect-ratio: 16/9; border: 0px;"></iframe>')
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

        expect(element.value).toBe('<img src="/a.png" alt="A" width="10" height="20" style="display: block; max-width: 100%; height: auto; margin-left: auto; margin-right: auto;">'
            + '<audio src="/b.mp3" title="b.mp3" controls="controls" preload="metadata" style="display: block; width: 100%;"></audio>'
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

        expect(element.value).toContain('<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"')
        expect(element.value).toContain('<h2>Heading</h2><ul style="list-style-type: none; padding-left: 0px;"><li style="display: flex; align-items: baseline; gap: 0.5em;"><input type="checkbox" disabled="disabled" checked="checked"><div><p>Done</p></div></li></ul><p><strong>bold</strong></p>')
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
