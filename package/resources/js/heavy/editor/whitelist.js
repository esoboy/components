/*
 * The format's whitelist in the editor, as the server applies it (RichTextFormat): the
 * tags kept, each with the attributes kept on it, and the CSS properties kept in their
 * style, values as written unless they could run or load something. Pasted and loaded
 * HTML keeps exactly that; what the editor writes itself is within it.
 */

import { Extension, Mark, Node } from '@tiptap/core'
import { restyle } from './styles.js'

// Blocks a generic container holds, as against text: it is a container of blocks then.
const BLOCK_TAGS = new Set(['address', 'article', 'aside', 'blockquote', 'center', 'details', 'dd', 'div', 'dl', 'dt', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'ul'])

// Tags no feature writes: kept as they are when the whitelist has them (layout's).
const CONTAINER_TAGS = ['section', 'div', 'article', 'aside', 'header', 'footer', 'main', 'nav', 'address', 'center', 'figure', 'figcaption', 'dl', 'dt', 'dd']
const INLINE_TAGS = ['small', 'big', 'abbr', 'cite', 'q', 'ins', 'kbd', 'samp', 'var', 'dfn', 'time', 'bdi', 'bdo', 'font']

// Attributes the editor's own nodes read and write: never kept twice as extras.
const OWN = new Set(['style', 'src', 'alt', 'title', 'width', 'height', 'href', 'target', 'rel', 'start', 'type', 'colspan', 'rowspan', 'colwidth', 'checked', 'disabled', 'download', 'poster', 'controls', 'preload', 'allowfullscreen', 'loading', 'open', 'display', 'encoding'])

/**
 * A style's declarations as [property, value], split on the semicolons outside
 * parentheses and quotes; a value still holding one goes.
 */
export function declarations(style) {
    const parts = []
    let part = ''
    let depth = 0
    let quote = null

    for (const character of String(style ?? '')) {
        if (quote) quote = character === quote ? null : quote
        else if (character === '"' || character === "'") quote = character
        else if (character === '(') depth++
        else if (character === ')') depth = Math.max(0, depth - 1)
        else if (character === ';' && ! depth) {
            parts.push(part)
            part = ''
            continue
        }

        part += character
    }

    return [...parts, part].flatMap(declaration => {
        const colon = declaration.indexOf(':')
        const value = declaration.slice(colon + 1).replace(/\s+/g, ' ').trim()

        return colon > 0 && value && ! value.includes(';') ? [[declaration.slice(0, colon).trim().toLowerCase(), value]] : []
    })
}

// A value that runs or loads nothing: no expression(), script, escapes or comments, and
// url()s on http(s) or relative addresses only.
export function safe(value) {
    if (/expression\s*\(|javascript:|vbscript:|-moz-binding|behavior|@import|[\\<>{}]|\/\*/i.test(value)) return false

    return [...value.matchAll(/url\(\s*(['"]?)(.*?)\1\s*\)/gi)].every(([, , url]) => {
        const scheme = url.trim().match(/^([a-z][a-z\d+.-]*):/i)?.[1]

        return ! scheme || ['http', 'https'].includes(scheme.toLowerCase())
    })
}

// Declarations as a style attribute, or null for none.
export function stringify(entries) {
    const style = [...entries].map(([property, value]) => `${property}: ${value}`).join('; ')

    return style || null
}

/**
 * A style with one property set (or removed, for no value), the rest as they were.
 */
export function setProperty(style, property, value) {
    const entries = new Map(declarations(style))

    entries.delete(property)
    if (value != null && value !== '') entries.set(property, value)

    return stringify(entries)
}

export function getProperty(style, property) {
    return new Map(declarations(style)).get(property) ?? null
}

/**
 * The format's whitelist: allows(tag, attribute), style(element) for what of an
 * element's style is kept, extras(element) for its other kept attributes.
 */
export function whitelist(format) {
    const tags = new Map(Object.entries(format.allow ?? {}).map(([tag, attributes]) => [tag, new Set(attributes)]))
    const css = new Set(format.css ?? [])

    const allows = (tag, attribute) => attribute ? !! tags.get(tag)?.has(attribute) : tags.has(tag)

    // As the browser writes it, so the same style written two ways (the server's hex,
    // the editor's rgb()) is the same.
    const clean = value => restyle(stringify(declarations(value).filter(([property, declared]) => css.has(property) && safe(declared))), {})

    return {
        tags,
        css,
        allows,
        clean,
        style: element => allows(element.tagName.toLowerCase(), 'style') ? clean(element.getAttribute('style')) : null,
        // The whitelisted attributes the node doesn't read itself, or null.
        extras(element, own = OWN) {
            const tag = element.tagName.toLowerCase()
            const kept = [...element.attributes].filter(({ name }) => ! own.has(name) && allows(tag, name))

            return kept.length ? Object.fromEntries(kept.map(({ name, value }) => [name, value])) : null
        },
        // All its whitelisted attributes, style cleaned: for tags kept as they are.
        attributes(element) {
            const tag = element.tagName.toLowerCase()
            const kept = [...element.attributes]
                .filter(({ name }) => allows(tag, name))
                .map(({ name, value }) => [name, name === 'style' ? clean(value) : value])
                .filter(([, value]) => value !== null)

            return Object.fromEntries(kept)
        },
        containers: CONTAINER_TAGS.filter(tag => tags.has(tag)),
        inlines: INLINE_TAGS.filter(tag => tags.has(tag)),
    }
}

/**
 * Nodes and marks keep their whitelisted style and other attributes through the editor:
 * style as style (what the editor's styles change), the rest as extras. Marks that
 * stand for a tag of their own (bold for <strong>, …) keep extras only, as a span they
 * are read from has its style read as a text style already.
 */
export function passthrough(list, styled, marks) {
    const style = { default: null, parseHTML: element => list.style(element), renderHTML: ({ style }) => style ? { style } : {} }
    const extras = { default: null, parseHTML: element => list.extras(element), renderHTML: ({ extras }) => extras ?? {} }

    return Extension.create({
        name: 'passthrough',

        addGlobalAttributes: () => [
            { types: styled, attributes: { style, extras } },
            { types: marks, attributes: { extras } },
        ],
    })
}

const hasBlock = element => [...element.children].some(child => BLOCK_TAGS.has(child.tagName.toLowerCase()))

/**
 * The tags no feature writes, kept as they are: blocks of blocks (a section of
 * paragraphs), blocks of text (a section of words, not wrapped in a paragraph), and
 * inline tags (small, abbr, font, …), each with its whitelisted attributes.
 */
export function generic(list) {
    const attrs = { tag: { default: 'div', rendered: false }, attributes: { default: {}, rendered: false } }
    const rules = (tags, priority, match) => tags.map(tag => ({ tag, priority, getAttrs: element => match(element) ? { tag, attributes: list.attributes(element) } : false }))
    const render = ({ node, mark }) => [(node ?? mark).attrs.tag, (node ?? mark).attrs.attributes, 0]

    return [
        list.containers.length && Node.create({
            name: 'container',
            group: 'block',
            content: 'block+',
            defining: true,
            addAttributes: () => attrs,
            parseHTML: () => rules(list.containers, 30, hasBlock),
            renderHTML: render,
        }),
        list.containers.length && Node.create({
            name: 'textContainer',
            group: 'block',
            content: 'inline*',
            addAttributes: () => attrs,
            parseHTML: () => rules(list.containers, 30, element => ! hasBlock(element)),
            renderHTML: render,
        }),
        list.inlines.length && Mark.create({
            name: 'inlineTag',
            excludes: '',
            addAttributes: () => attrs,
            parseHTML: () => rules(list.inlines, 40, () => true),
            renderHTML: render,
        }),
    ].filter(Boolean)
}
