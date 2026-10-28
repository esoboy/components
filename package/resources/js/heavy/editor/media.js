/*
 * What the Editor inserts for attachments ({ url, name, mime | type, size, alt, width,
 * height, poster }) and embed links, and the media's inline style.
 */

const EXTENSIONS = {
    image: /\.(avif|bmp|gif|jpe?g|png|svg|webp)$/i,
    audio: /\.(aac|flac|m4a|mp3|oga|ogg|opus|wav)$/i,
    video: /\.(m4v|mov|mp4|ogv|webm)$/i,
}

// image, audio, video or file: by its MIME type, else its address's extension.
export function kindOf(attachment) {
    const mime = String(attachment.mime ?? attachment.type ?? '').toLowerCase()
    const kind = mime.split('/')[0]

    if (['image', 'audio', 'video'].includes(kind)) return kind
    if (mime) return 'file'

    const path = String(attachment.url ?? '').split(/[?#]/)[0]

    return Object.keys(EXTENSIONS).find(name => EXTENSIONS[name].test(path)) ?? 'file'
}

// The kinds an editor takes: its media features, files for any kind besides.
export function accepted(features) {
    return ['image', 'audio', 'video', 'file'].filter(kind => features.includes(kind))
}

const number = value => Number.isFinite(Number(value)) && Number(value) > 0 ? Math.round(Number(value)) : null

function basename(url) {
    try {
        return decodeURIComponent(new URL(url, document.baseURI).pathname.split('/').pop()) || url
    } catch {
        return url
    }
}

// 1.2 MB: the number in the page's language, the unit's symbol.
export function formatSize(bytes) {
    bytes = Number(bytes)

    if (! Number.isFinite(bytes) || bytes < 0) return null

    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    const exponent = Math.min(units.length - 1, Math.floor(Math.log(Math.max(bytes, 1)) / Math.log(1024)))
    const number = new Intl.NumberFormat(document.documentElement.lang || undefined, { maximumFractionDigits: exponent ? 1 : 0 }).format(bytes / 1024 ** exponent)

    return `${number} ${units[exponent]}`
}

/**
 * The node for an attachment, as the format allows: a kind it leaves out goes in as a
 * file, or not at all without files. Addresses must be http(s) or relative.
 */
export function attachmentNode(attachment, features) {
    const url = String(attachment?.url ?? '').trim()

    if (! url || ! safeUrl(url)) return null

    let kind = kindOf(attachment)

    if (! features.includes(kind)) kind = 'file'
    if (! features.includes(kind)) return null

    const name = attachment.name ? String(attachment.name) : null

    if (kind === 'image') return { type: 'image', attrs: { src: url, alt: attachment.alt ?? name ?? '', width: number(attachment.width), height: number(attachment.height) } }
    if (kind === 'video') return { type: 'video', attrs: { src: url, poster: attachment.poster && safeUrl(attachment.poster) ? attachment.poster : null, width: number(attachment.width), height: number(attachment.height), title: name } }
    if (kind === 'audio') return { type: 'audio', attrs: { src: url, title: name } }

    return { type: 'file', attrs: { href: url, name: name ?? basename(url), mime: attachment.mime ?? attachment.type ?? null, size: attachment.size == null ? null : formatSize(attachment.size) } }
}

// http(s) or relative; never data:, javascript:, …
export function safeUrl(url) {
    const scheme = String(url).trim().match(/^([a-z][a-z\d+.-]*):/i)?.[1]

    return ! scheme || ['http', 'https'].includes(scheme.toLowerCase())
}

/**
 * The player address for a share link, a player address, or the src of pasted embed
 * code; null when no platform plays it.
 */
export function embedSource(input, platforms) {
    let url = String(input ?? '').trim()

    url = url.match(/<iframe[^>]*\ssrc=["']([^"']+)["']/i)?.[1]?.replaceAll('&amp;', '&') ?? url

    for (const [pattern, src] of platforms) {
        const match = url.match(new RegExp(pattern))

        if (match) return src.replace(/\$(\d)/g, (_, index) => match[index] ?? '')
    }

    return playable(url, platforms) ? url : null
}

// Whether an address is on one of the platforms' players.
export function playable(url, platforms) {
    try {
        const { protocol, host } = new URL(url)

        return protocol === 'https:' && platforms.some(([, src]) => new URL(src.replace(/\$\d/g, '')).host === host)
    } catch {
        return false
    }
}

/**
 * Images, videos and embeds: blocks no wider than their container, a width of their
 * own (480px, 50%) or their natural one, aligned with margins.
 */
export function mediaStyle({ size, align }, more = '') {
    const margins = { center: ';margin-left:auto;margin-right:auto', right: ';margin-left:auto' }[align] ?? ''

    return `display:block${size ? `;width:${size}` : ''};max-width:100%;height:auto${more}${margins}`
}

export function parseSize(element) {
    const width = element.style.width

    return /^(\d{1,4}px|([1-9]\d?|100)%)$/.test(width) ? width : null
}

export function parseAlign(element) {
    const { marginLeft, marginRight } = element.style

    if (marginLeft === 'auto') return marginRight === 'auto' ? 'center' : 'right'

    return 'left'
}
