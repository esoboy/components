import contract from '../../content/schema.json'

const policy = contract.iframe

export function frameAttributes({ src, title, width, height }) {
    const address = typeof src === 'string' ? src.trim().replace(/^\/\//, 'https://') : ''
    if (!/^https?:\/\/[^/?#@]+(?:[/?#]|$)/i.test(address) || /[\s\\<>]/u.test(address)) return null
    const url = URL.parse(address)
    if (!url?.hostname || url.username || url.password) return null
    const dimension = (value, fallback) => (/^[1-9]\d{0,4}$/.test(value) ? Number(value) : fallback)
    const sandbox = policy.playerOrigins.includes(url.origin)
        ? `${policy.sandbox} allow-same-origin`
        : policy.sandbox
    return {
        src: address,
        ...(title?.trim() && { title: title.trim() }),
        width: dimension(width, policy.width),
        height: dimension(height, policy.height),
        ...policy.attributes,
        sandbox,
    }
}

export function readFrame(element) {
    return frameAttributes(
        Object.fromEntries(
            ['src', 'title', 'width', 'height'].map((key) => [key, element.getAttribute(key)]),
        ),
    )
}
