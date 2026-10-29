/*
 * The Editor's uploads, to the app's uploader (config ui.editor.uploader) through the
 * package's route (data-upload): one file a request, three at a time, each answered with
 * the attachment the uploader made of it ({ url, name, mime, … }) or null. An image on
 * another site the browser couldn't read goes as its address, for the server to fetch.
 */

const AT_ONCE = 3

const waiting = []
let running = 0

// Why an upload failed, as the app said it (its validation message), or '' for no reason given.
export class UploadError extends Error {}

/**
 * Sends { file | url, source } (and the page's sealed context) to the endpoint, with
 * the page's CSRF token.
 */
export function upload(endpoint, { file = null, url = null, source, context = null, token = null }) {
    return new Promise((resolve, reject) => {
        waiting.push(() => send(endpoint, { file, url, source, context, token }).then(resolve, reject))
        next()
    })
}

function next() {
    while (running < AT_ONCE && waiting.length) {
        running++
        waiting.shift()().finally(() => {
            running--
            next()
        })
    }
}

async function send(endpoint, { file, url, source, context, token }) {
    const body = new FormData()

    body.append('source', source)
    if (file) body.append('file', file, file.name)
    if (url) body.append('url', url)
    if (context) body.append('context', context)

    const response = await fetch(endpoint, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...token ? { 'X-CSRF-TOKEN': token } : {} },
    })
    const answer = await response.json().catch(() => ({}))

    if (response.ok) return answer.attachment ?? null

    // The app's reason: a validation message, an authorization one; none from a server error.
    const reason = response.status === 422 ? Object.values(answer.errors ?? {}).flat()[0] ?? answer.message : response.status < 500 && response.status !== 419 ? answer.message : null

    throw new UploadError(typeof reason === 'string' ? reason : '')
}
