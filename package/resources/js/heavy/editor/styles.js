/*
 * The Editor's styles, all inline style with the format's fixed values: palette colors
 * and highlights, fonts, sizes, alignment, indents, first-line indents and line heights.
 * Values it wrote come back as they were; pasted ones snap to the nearest it offers, or
 * go: text near black or white, a white background, an unknown font, …
 */

import { Extension } from '@tiptap/core'
import Highlight from '@tiptap/extension-highlight'
import { TextStyle } from '@tiptap/extension-text-style'
import TextAlign from '@tiptap/extension-text-align'

const BLOCKS = ['paragraph', 'heading']

let canvas = null

// [r, g, b] of #rgb, #rrggbb, rgb(), rgba() or a color's name (as a canvas reads it), or
// null for anything else or transparent.
export function rgb(value) {
    value = String(value ?? '').trim().toLowerCase()

    if (/^[a-z]+$/.test(value) && value !== 'transparent') {
        canvas ??= document.createElement('canvas').getContext?.('2d') ?? null
        if (canvas) {
            canvas.fillStyle = '#010203'
            canvas.fillStyle = value
            if (canvas.fillStyle !== '#010203') value = canvas.fillStyle
        }
    }

    const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/)?.[1]

    if (hex) return (hex.length === 3 ? [...hex].map(digit => digit + digit) : hex.match(/../g)).map(pair => parseInt(pair, 16))

    const channels = value.match(/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)(?:\s*[,/]\s*([\d.]+)(%?))?\s*\)$/)

    if (! channels || (channels[4] !== undefined && parseFloat(channels[4]) === 0)) return null

    return channels.slice(1, 4).map(Number)
}

// CIELAB of an sRGB color (D65), where distance is how far apart colors look.
function lab(color) {
    const [r, g, b] = color.map(channel => (channel /= 255) <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4)
    const f = value => value > 216 / 24389 ? Math.cbrt(value) : (24389 / 27 * value + 16) / 116
    const x = f((0.4124 * r + 0.3576 * g + 0.1805 * b) / 0.95047)
    const y = f(0.2126 * r + 0.7152 * g + 0.0722 * b)
    const z = f((0.0193 * r + 0.1192 * g + 0.9505 * b) / 1.08883)

    return [116 * y - 16, 500 * (x - y), 200 * (y - z)]
}

const distance = (a, b) => Math.hypot(...lab(a).map((value, index) => value - lab(b)[index]))

/**
 * The palette color a value is, or the nearest one; null for no color or one that only
 * follows the page: a gray close to black or white (or, for backgrounds, near white).
 */
export function nearestColor(value, palette, { background = false } = {}) {
    const color = rgb(value)

    if (! color) return null

    const exact = palette.find(candidate => distance(rgb(candidate), color) === 0)

    if (exact) return exact

    const lightness = (Math.max(...color) + Math.min(...color)) / 2
    const gray = Math.max(...color) - Math.min(...color) < 24

    if (background ? lightness > 240 || (gray && lightness < 40) : gray && (lightness < 64 || lightness > 200)) return null

    return palette.reduce((nearest, candidate) => distance(rgb(candidate), color) < distance(rgb(nearest), color) ? candidate : nearest)
}

// A length in px: px, pt, em and rem (at 16px), or NaN.
function pixels(value) {
    const [, number, unit = 'px'] = String(value ?? '').trim().toLowerCase().match(/^([\d.]+)(px|pt|r?em)?$/) ?? []

    return parseFloat(number) * ({ px: 1, pt: 4 / 3, em: 16, rem: 16 })[unit]
}

const nearest = (number, numbers) => numbers.reduce((best, candidate) => Math.abs(candidate - number) < Math.abs(best - number) ? candidate : best)

// The family list's names, unquoted and lower case.
const families = value => String(value ?? '').split(',').map(name => name.trim().replace(/^['"]|['"]$/g, '').toLowerCase()).filter(Boolean)

/**
 * The font whose stack has a name of a value, the first that one does ("宋体, SimSun" is
 * SimSun's, and so is "Songti SC"); a generic family only when it comes first, as pages
 * end every stack with one.
 */
export function nearestFont(value, fonts) {
    const names = families(value)
    const named = names.map(name => fonts.find(font => families(font).length > 1 && families(font).slice(0, -1).includes(name))).find(Boolean)

    return named ?? fonts.find(font => families(font).length === 1 && families(font)[0] === names[0]) ?? null
}

export function nearestSize(value, sizes) {
    const size = pixels(value)

    return size > 0 ? `${nearest(size, sizes)}px` : null
}

// textStyle's color, font and size: each its own style, or nothing.
function textStyles(format) {
    const attributes = {}

    if (format.features.includes('color')) {
        attributes.color = { default: null, parseHTML: element => nearestColor(element.style.color, format.colors), renderHTML: ({ color }) => color ? { style: `color:${color}` } : {} }
    }

    if (format.features.includes('font-family')) {
        attributes.fontFamily = { default: null, parseHTML: element => nearestFont(element.style.fontFamily, format.fonts), renderHTML: ({ fontFamily }) => fontFamily ? { style: `font-family:${fontFamily}` } : {} }
    }

    if (format.features.includes('font-size')) {
        attributes.fontSize = { default: null, parseHTML: element => nearestSize(element.style.fontSize, format.sizes), renderHTML: ({ fontSize }) => fontSize ? { style: `font-size:${fontSize}` } : {} }
    }

    return attributes
}

// The block styles: indent (a level of 2em), first-line indent and line height.
function blockStyles(format) {
    const attributes = {}

    if (format.features.includes('indent')) {
        attributes.indent = {
            default: 0,
            parseHTML: element => Math.min(format.indents, Math.round(pixels(element.style.marginLeft) / 32) || 0),
            renderHTML: ({ indent }) => indent ? { style: `margin-left:${indent * 2}em` } : {},
        }
    }

    if (format.features.includes('text-indent')) {
        attributes.textIndent = {
            default: false,
            parseHTML: element => pixels(element.style.textIndent) >= 16,
            renderHTML: ({ textIndent }) => textIndent ? { style: 'text-indent:2em' } : {},
        }
    }

    if (format.features.includes('line-height')) {
        attributes.lineHeight = {
            default: null,
            parseHTML: element => /^[\d.]+$/.test(element.style.lineHeight) ? format.lineHeights.reduce((best, height) => Math.abs(height - element.style.lineHeight) < Math.abs(best - element.style.lineHeight) ? height : best) : null,
            renderHTML: ({ lineHeight }) => lineHeight ? { style: `line-height:${lineHeight}` } : {},
        }
    }

    return attributes
}

// Sets attributes on every paragraph and heading the selection touches (update returns
// them, or false to leave one be); possible wherever there is one.
function setBlocks(update) {
    return ({ tr, state, dispatch }) => {
        let found = false

        state.doc.nodesBetween(state.selection.from, state.selection.to, (node, position) => {
            if (! BLOCKS.includes(node.type.name)) return

            const attributes = update(node.attrs)

            found = true
            if (attributes && dispatch) tr.setNodeMarkup(position, undefined, { ...node.attrs, ...attributes })

            return false
        })

        return found
    }
}

export function styleExtensions(format) {
    const has = feature => format.features.includes(feature)
    const extensions = []
    const text = textStyles(format)
    const blocks = blockStyles(format)

    if (Object.keys(text).length) {
        extensions.push(TextStyle.extend({
            // A span is kept for a style it can write, not any style.
            parseHTML() {
                return [{ tag: 'span[style]', consuming: false, getAttrs: element => Object.values(text).some(({ parseHTML }) => parseHTML(element)) ? {} : false }]
            },
            addAttributes: () => text,
        }).configure({ mergeNestedSpanStyles: true }))
    }

    if (has('highlight')) {
        // Outside text colors, so a colored word stays its color on the highlight.
        extensions.push(Highlight.extend({
            priority: 102,
            // Word and Google Docs highlight with a span's background.
            parseHTML: () => [{ tag: 'mark' }, { tag: 'span[style*="background"]', consuming: false, getAttrs: element => nearestColor(element.style.backgroundColor, format.highlights, { background: true }) ? null : false }],
            addAttributes: () => ({
                color: {
                    default: format.highlights[0],
                    parseHTML: element => nearestColor(element.style.backgroundColor, format.highlights, { background: true }) ?? format.highlights[0],
                    renderHTML: ({ color }) => ({ style: `background-color:${color};color:${format.highlightText}` }),
                },
            }),
        }).configure({ multicolor: true }))
    }

    if (has('text-align')) {
        extensions.push(TextAlign.extend({
            addGlobalAttributes() {
                return [{
                    types: this.options.types,
                    attributes: {
                        textAlign: {
                            default: null,
                            parseHTML: element => this.options.alignments.includes(element.style.textAlign) ? element.style.textAlign : null,
                            renderHTML: ({ textAlign }) => textAlign ? { style: `text-align:${textAlign}` } : {},
                        },
                    },
                }]
            },
        }).configure({ types: BLOCKS, alignments: ['left', 'center', 'right', 'justify'] }))
    }

    if (Object.keys(blocks).length) {
        extensions.push(Extension.create({
            name: 'blockStyles',

            // A first-line indent is for paragraphs, not headings.
            addGlobalAttributes: () => {
                const { textIndent, ...others } = blocks

                return [{ types: BLOCKS, attributes: others }, ...textIndent ? [{ types: ['paragraph'], attributes: { textIndent } }] : []]
            },

            addCommands: () => ({
                // In a list, a level of the list.
                indent: () => props => {
                    const item = ['taskItem', 'listItem'].find(name => props.editor.isActive(name))

                    if (item) return props.commands.sinkListItem(item)

                    return has('indent') && setBlocks(({ indent }) => indent < format.indents && { indent: indent + 1 })(props)
                },
                outdent: () => props => {
                    const item = ['taskItem', 'listItem'].find(name => props.editor.isActive(name))

                    if (item) return props.commands.liftListItem(item)

                    return has('indent') && setBlocks(({ indent }) => indent > 0 && { indent: indent - 1 })(props)
                },
                toggleTextIndent: () => props => {
                    const textIndent = ! props.editor.getAttributes('paragraph').textIndent

                    return setBlocks(() => ({ textIndent }))(props)
                },
                setLineHeight: lineHeight => setBlocks(() => ({ lineHeight })),
            }),

            addKeyboardShortcuts() {
                return {
                    'Mod-]': () => this.editor.commands.indent(),
                    'Mod-[': () => this.editor.commands.outdent(),
                }
            },
        }))
    }

    return extensions
}

// Takes formatting off: marks, and the block styles (headings, lists and quotes stay).
export const ClearFormatting = Extension.create({
    name: 'clearFormatting',

    addCommands: () => ({
        clearFormatting: () => ({ chain }) => chain()
            .unsetAllMarks()
            .command(setBlocks(attributes => {
                const cleared = Object.fromEntries(['textAlign', 'indent', 'textIndent', 'lineHeight'].filter(name => name in attributes).map(name => [name, name === 'indent' ? 0 : name === 'textIndent' ? false : null]))

                return Object.keys(cleared).some(name => attributes[name] !== cleared[name]) && cleared
            }))
            .run(),
    }),
})
