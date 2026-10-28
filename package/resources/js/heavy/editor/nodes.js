/*
 * The Editor's blocks beyond StarterKit, each written as plain HTML that reads anywhere
 * and parsed back from it: no data-* attributes or classes, inline style for the rest.
 */

import { Node, mergeAttributes } from '@tiptap/core'
import CodeBlock from '@tiptap/extension-code-block'
import { Details, DetailsContent, DetailsSummary } from '@tiptap/extension-details'
import Image from '@tiptap/extension-image'
import { TaskItem, TaskList } from '@tiptap/extension-list'
import { Table, TableCell, TableHeader, TableRow, createColGroup } from '@tiptap/extension-table'
import { mediaStyle, parseAlign, parseSize, playable, safeUrl } from './media.js'
import { resizable } from './resize.js'

// Attributes the node writes itself, so they don't render as attributes of their own.
const own = (fallback, parseHTML) => ({ default: fallback, parseHTML, renderHTML: () => ({}) })

// A box first in the item (alone or in a label), as Markdown's, GitHub's and Tiptap's are.
const checkbox = item => item.querySelector(':scope > input[type="checkbox"], :scope > label > input[type="checkbox"]')

/**
 * Task lists: <ul> with a box opening each item, styled inline as a list without bullets.
 */
export const tasks = labels => [
    TaskList.extend({
        parseHTML: () => [{ tag: 'ul', priority: 60, getAttrs: list => [...list.children].some(item => item.matches('li') && checkbox(item)) ? null : false }],
        renderHTML: ({ HTMLAttributes }) => ['ul', mergeAttributes(HTMLAttributes, { style: 'list-style-type:none;padding-left:0' }), 0],
    }),
    TaskItem.extend({
        addAttributes: () => ({ checked: { default: false, keepOnSplit: false, parseHTML: item => checkbox(item)?.hasAttribute('checked') ?? false, renderHTML: () => ({}) } }),
        parseHTML: () => [{
            tag: 'li',
            priority: 60,
            getAttrs: item => checkbox(item) ? null : false,
            // Its text: the div after the box, or what is left without the box.
            contentElement: item => {
                const content = item.querySelector(':scope > div')

                if (content) return content

                const rest = item.cloneNode(true)

                checkbox(rest)?.closest('label, input')?.remove()
                if (rest.firstChild instanceof Text) rest.firstChild.textContent = rest.firstChild.textContent.trimStart()

                return rest
            },
        }],
        renderHTML: ({ node, HTMLAttributes }) => ['li', mergeAttributes(HTMLAttributes, { style: 'display:flex;align-items:baseline;gap:0.5em' }), ['input', { type: 'checkbox', disabled: 'disabled', checked: node.attrs.checked ? 'checked' : null }], ['div', 0]],
    }).configure({ nested: true, a11y: { checkboxLabel: node => `${labels.task}: ${node.textContent}` } }),
]

/**
 * Collapsible blocks: native <details> with its <summary>, the rest in a div; closed
 * when shown, whatever they were while writing.
 */
export const collapsible = labels => [
    Details.configure({
        persist: false,
        renderToggleButton: ({ element, isOpen }) => {
            element.setAttribute('aria-label', isOpen ? labels.collapse : labels.expand)
            element.setAttribute('aria-expanded', String(isOpen))
        },
    }),
    DetailsSummary,
    DetailsContent.extend({
        parseHTML: () => [{ tag: 'div[data-type="detailsContent"]' }, { tag: 'details > div' }],
        renderHTML: ({ HTMLAttributes }) => ['div', HTMLAttributes, 0],
    }),
]

// A code block without a language: plain <pre><code>, Tab indenting in it.
export const PlainCodeBlock = CodeBlock.extend({
    addAttributes: () => ({}),
}).configure({ enableTabIndentation: true, tabSize: 4 })

// A cell's column widths: Tiptap's colwidth, else its columns' <col> widths.
function columnWidths(cell) {
    if (cell.getAttribute('colwidth')) return cell.getAttribute('colwidth').split(',').map(Number)

    const columns = [...cell.closest('table')?.querySelectorAll(':scope > colgroup > col') ?? []]
    const span = Number(cell.getAttribute('colspan')) || 1
    let index = 0

    for (const sibling of cell.parentElement?.children ?? []) {
        if (sibling === cell) break
        index += Number(sibling.getAttribute('colspan')) || 1
    }

    const widths = columns.slice(index, index + span).map(column => parseInt(column.style.width, 10))

    return widths.length === span && widths.every(width => width > 0) ? widths : null
}

const cell = (extension, style) => extension.extend({
    addAttributes() {
        return { ...this.parent?.(), colwidth: own(null, columnWidths) }
    },
    renderHTML({ HTMLAttributes, ...props }) {
        return this.parent({ ...props, HTMLAttributes: mergeAttributes(HTMLAttributes, { style }) })
    },
})

/**
 * Tables: collapsed borders and padded cells inline; column widths on <col>, the table
 * as wide as they add up to (or the page) and scrolling in a div when wider.
 */
export function tables(styles) {
    return [
        Table.extend({
            renderHTML({ node }) {
                const { colgroup, tableWidth } = createColGroup(node, this.options.cellMinWidth)

                return ['div', { style: 'overflow-x:auto' }, ['table', { style: `border-collapse:collapse;width:${tableWidth || '100%'}` }, colgroup, ['tbody', 0]]]
            },
        }).configure({ resizable: true, cellMinWidth: 48, allowTableNodeSelection: true }),
        TableRow,
        cell(TableHeader, `${styles.cell};${styles.headerCell}`),
        cell(TableCell, styles.cell),
    ]
}

const mediaAttributes = () => ({
    size: own(null, parseSize),
    align: own('center', parseAlign),
})

// In its box while editing: as wide as the box once sized, else its own width.
const boxed = ({ attrs }) => `display:block;max-width:100%;height:auto;width:${attrs.size ? '100%' : 'auto'}`

// Images: never a data: address (pasted screenshots go to the app as files instead).
export const Picture = Image.extend({
    addAttributes() {
        return { ...this.parent?.(), ...mediaAttributes() }
    },
    parseHTML: () => [{ tag: 'img[src]', getAttrs: image => safeUrl(image.getAttribute('src')) ? null : false }],
    renderHTML: ({ node, HTMLAttributes }) => ['img', mergeAttributes(HTMLAttributes, { style: mediaStyle(node.attrs) })],

    addNodeView: () => resizable('img', ({ attrs }) => ({ src: attrs.src, alt: attrs.alt, title: attrs.title, width: attrs.width, height: attrs.height }), boxed),
})

const source = element => element.getAttribute('src') ?? element.querySelector('source[src]')?.getAttribute('src')

export const Video = Node.create({
    name: 'video',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes: () => ({
        src: { default: null, parseHTML: source },
        poster: { default: null },
        width: { default: null },
        height: { default: null },
        title: { default: null },
        ...mediaAttributes(),
    }),

    parseHTML: () => [{ tag: 'video', getAttrs: video => source(video) && safeUrl(source(video)) ? null : false }],

    renderHTML: ({ node, HTMLAttributes }) => ['video', mergeAttributes(HTMLAttributes, { controls: 'controls', preload: 'metadata', style: mediaStyle(node.attrs) })],

    addNodeView: () => resizable('video', ({ attrs }) => ({ src: attrs.src, poster: attrs.poster, title: attrs.title, width: attrs.width, height: attrs.height, controls: true, preload: 'metadata' }), boxed),
})

export const Audio = Node.create({
    name: 'audio',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes: () => ({
        src: { default: null, parseHTML: source },
        title: { default: null },
    }),

    parseHTML: () => [{ tag: 'audio', getAttrs: audio => source(audio) && safeUrl(source(audio)) ? null : false }],

    renderHTML: ({ HTMLAttributes }) => ['audio', mergeAttributes(HTMLAttributes, { controls: 'controls', preload: 'metadata', style: 'display:block;width:100%' })],
})

/**
 * Files: a download link drawn as a card (clip, name, size) with inline style, so it
 * reads as one anywhere.
 */
export function file(card) {
    return Node.create({
        name: 'file',
        group: 'block',
        atom: true,
        draggable: true,

        addAttributes: () => ({
            href: own(null, link => link.getAttribute('href')),
            name: own(null, link => link.getAttribute('download') || link.querySelector(':scope > span:nth-of-type(2)')?.textContent || link.textContent.trim()),
            mime: own(null, link => link.getAttribute('type')),
            size: own(null, link => link.querySelector(':scope > span:nth-of-type(3)')?.textContent ?? null),
        }),

        parseHTML: () => [{ tag: 'a[download][href]', priority: 60, getAttrs: link => safeUrl(link.getAttribute('href')) ? null : false }],

        renderHTML: ({ node }) => [
            'a',
            { href: node.attrs.href, download: node.attrs.name ?? '', type: node.attrs.mime, style: card },
            ['span', {}, '📎'],
            ['span', {}, node.attrs.name ?? ''],
            ...node.attrs.size ? [['span', { style: 'opacity:0.6' }, node.attrs.size]] : [],
        ],
    })
}

/**
 * Embeds: a platform's player in a 16:9 iframe; other addresses don't load. While
 * editing it takes no clicks until selected, so a click selects it.
 */
export function embed(platforms) {
    return Node.create({
        name: 'embed',
        group: 'block',
        atom: true,
        draggable: true,

        addAttributes: () => ({
            src: { default: null },
            title: { default: null },
            ...mediaAttributes(),
        }),

        parseHTML: () => [{ tag: 'iframe[src]', getAttrs: frame => playable(frame.getAttribute('src'), platforms) ? null : false }],

        renderHTML: ({ node, HTMLAttributes }) => ['iframe', mergeAttributes(HTMLAttributes, { allowfullscreen: 'allowfullscreen', loading: 'lazy', style: mediaStyle({ ...node.attrs, size: node.attrs.size ?? '100%' }, ';aspect-ratio:16/9;border:0') })],

        addNodeView: () => resizable('iframe', ({ attrs }) => ({ src: attrs.src, title: attrs.title, allowfullscreen: true, loading: 'lazy' }), () => 'display:block;width:100%;height:auto;aspect-ratio:16/9;border:0'),
    })
}

// A formula's MathML as an element, or nothing to show yet.
function mathElement(mathml) {
    const template = document.createElement('template')

    template.innerHTML = mathml ?? ''

    return template.content.querySelector('math')
}

/**
 * Formulas: MathML, which browsers show natively, carrying its LaTeX in an annotation
 * to edit it again. KaTeX writes the MathML, loaded when a formula is first edited.
 */
export function formulas() {
    const formula = (name, inline) => Node.create({
        name,
        group: inline ? 'inline' : 'block',
        inline,
        atom: true,
        draggable: ! inline,

        addAttributes: () => ({
            latex: { default: '', rendered: false, parseHTML: math => math.querySelector('annotation[encoding="application/x-tex"]')?.textContent ?? '' },
            mathml: { default: null, rendered: false, parseHTML: math => math.outerHTML },
        }),

        parseHTML: () => [{ tag: inline ? 'math' : 'math[display="block"]', priority: inline ? 50 : 60 }],

        renderHTML: ({ node }) => mathElement(node.attrs.mathml) ?? ['math', inline ? {} : { display: 'block' }],

        addNodeView: () => ({ node }) => {
            const dom = document.createElement(inline ? 'span' : 'div')

            dom.dataset.editorMath = inline ? 'inline' : 'block'
            dom.append(mathElement(node.attrs.mathml) ?? '')

            return { dom }
        },
    })

    return [formula('inlineMath', true), formula('blockMath', false)]
}

/**
 * MathML for LaTeX, with its source annotated; throws on LaTeX KaTeX can't read.
 */
export async function toMathML(latex, display) {
    const { default: katex } = await import('katex')
    const html = katex.renderToString(latex, { output: 'mathml', displayMode: display, throwOnError: true })

    return mathElement(html).outerHTML
}
