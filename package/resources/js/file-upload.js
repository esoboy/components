/*
 * <ui-file-upload>
 *
 * Livewire uploads the file input behind wire:model; this adds what a drop area needs
 * around it: dropping files in, and showing them until the upload is done (a chip per
 * file with the progress for File Upload, the new picture for Image Upload). The
 * server's list of uploaded files takes over once Livewire re-renders.
 */

const svg = paths => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`
const FILE_ICON = svg('<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>')

export function formatSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    const power = bytes > 0 ? Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1) : 0

    return power === 0 ? `${bytes} B` : `${Number((bytes / 1024 ** power).toFixed(1))} ${units[power]}`
}

/** Whether a file matches an accept attribute such as "image/*,.pdf". */
export function accepts(accept, file) {
    if (! accept) return true

    return accept.split(',').map(rule => rule.trim().toLowerCase()).filter(Boolean).some(rule => {
        if (rule.startsWith('.')) return file.name.toLowerCase().endsWith(rule)
        if (rule.endsWith('/*')) return file.type.startsWith(rule.slice(0, -1))

        return file.type === rule
    })
}

export class FileUploadElement extends HTMLElement {
    #urls = []

    connectedCallback() {
        const zone = this.dropzone

        if (! zone) return

        zone.addEventListener('dragenter', event => this.#onDrag(event))
        zone.addEventListener('dragover', event => this.#onDrag(event))
        zone.addEventListener('dragleave', event => ! zone.contains(event.relatedTarget) && zone.removeAttribute('data-dragging'))
        zone.addEventListener('drop', event => this.#onDrop(event))

        this.addEventListener('livewire-upload-start', () => this.#start())
        this.addEventListener('livewire-upload-progress', event => this.#progress(event.detail?.progress ?? 0))
        for (const type of ['livewire-upload-finish', 'livewire-upload-error', 'livewire-upload-cancel']) {
            this.addEventListener(type, () => this.#end())
        }
    }

    disconnectedCallback() {
        this.#revoke()
    }

    get input() {
        return this.querySelector('[data-slot="file-upload-input"]')
    }

    get dropzone() {
        return this.input?.closest('label')
    }

    #onDrag(event) {
        if (this.input?.disabled || ! event.dataTransfer?.types?.includes('Files')) return

        event.preventDefault()
        event.dataTransfer.dropEffect = 'copy'
        this.dropzone.setAttribute('data-dragging', '')
    }

    // Dropped files go into the input, whose change event Livewire uploads.
    #onDrop(event) {
        const input = this.input

        this.dropzone.removeAttribute('data-dragging')

        if (! input || input.disabled || ! event.dataTransfer?.files?.length) return

        event.preventDefault()

        let files = [...event.dataTransfer.files].filter(file => accepts(input.accept, file))

        if (! input.multiple) files = files.slice(0, 1)
        if (! files.length) return

        const transfer = new DataTransfer()

        files.forEach(file => transfer.items.add(file))
        input.files = transfer.files
        input.dispatchEvent(new Event('change', { bubbles: true }))
    }

    #start() {
        const files = [...(this.input?.files ?? [])]

        this.setAttribute('data-uploading', '')
        this.#revoke()

        // Image Upload: show the new picture straight away.
        const image = this.querySelector('[data-slot="image-upload-image"]')

        if (image && files[0]?.type.startsWith('image/')) {
            image.src = this.#url(files[0])
            image.hidden = false
            this.querySelector('[data-slot="image-upload-placeholder"]')?.setAttribute('hidden', '')
        }

        // File Upload: a chip per file, with the progress.
        const pending = this.querySelector('[data-file-upload-pending]')

        if (pending) {
            pending.replaceChildren(...files.map(file => this.#chip(file)))
            pending.closest('[data-file-upload-list]')?.removeAttribute('hidden')
        }

        this.querySelector('[data-slot="file-upload-progress"]')?.removeAttribute('hidden')
        this.#progress(0)
    }

    #progress(percent) {
        this.style.setProperty('--file-upload-progress', `${percent}%`)

        const label = (this.dataset.uploadingLabel ?? '').replace(':progress', percent)

        this.querySelectorAll('[data-file-upload-pending] [data-slot="attachment-description"]').forEach(description => (description.textContent = label))
    }

    #end() {
        this.removeAttribute('data-uploading')
        this.querySelector('[data-slot="file-upload-progress"]')?.setAttribute('hidden', '')

        // Livewire's re-render brings the uploaded files; drop the stand-ins once it has.
        queueMicrotask(() => {
            const pending = this.querySelector('[data-file-upload-pending]')

            pending?.replaceChildren()

            const list = pending?.closest('[data-file-upload-list]')

            if (list && ! list.querySelector('[data-slot="attachment"]')) list.setAttribute('hidden', '')
        })
    }

    #chip(file) {
        const vertical = this.dataset.orientation === 'vertical'
        const chip = document.createElement('div')
        const media = document.createElement('div')
        const content = document.createElement('div')
        const title = document.createElement('span')
        const description = document.createElement('span')

        Object.assign(chip.dataset, { slot: 'attachment', state: 'uploading', size: 'default', orientation: vertical ? 'vertical' : 'horizontal' })
        media.dataset.slot = 'attachment-media'

        if (file.type.startsWith('image/')) {
            media.dataset.variant = 'image'
            media.append(Object.assign(document.createElement('img'), { src: this.#url(file), alt: '' }))
        } else {
            media.dataset.variant = 'icon'
            media.innerHTML = FILE_ICON
        }

        content.dataset.slot = 'attachment-content'
        title.dataset.slot = 'attachment-title'
        title.textContent = file.name
        description.dataset.slot = 'attachment-description'
        description.textContent = formatSize(file.size)
        content.append(title, description)
        chip.append(media, content)

        return chip
    }

    #url(file) {
        const url = URL.createObjectURL(file)

        this.#urls.push(url)

        return url
    }

    #revoke() {
        this.#urls.forEach(url => URL.revokeObjectURL(url))
        this.#urls = []
    }
}

export function defineFileUpload(registry = window.customElements) {
    if (! registry.get('ui-file-upload')) registry.define('ui-file-upload', FileUploadElement)
}
