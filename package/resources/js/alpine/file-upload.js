/*
 * <eso-file-upload>
 *
 * Livewire uploads the file input behind wire:model; this adds what a drop area needs
 * around it: dropping files in, and showing them until the upload is done (a chip per
 * file with the progress for File Upload, the new picture for Image Upload). The
 * server's list of uploaded files takes over once Livewire re-renders.
 */

import { disconnected, listen, emit } from './interaction.js'
import { clone, readTemplates } from '../support/templates.js'

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

export default () => ({
    _templates: null,
    _urls: null,
    init() {
        this._urls = []
        this._templates = {}

        this._templates = readTemplates(this.$el)

        const zone = this.dropzone

        if (! zone) return

        listen(this, zone, 'dragenter', event => this._onDrag(event), {})
        listen(this, zone, 'dragover', event => this._onDrag(event), {})
        listen(this, zone, 'dragleave', event => ! zone.contains(event.relatedTarget) && zone.removeAttribute('data-dragging'), {})
        listen(this, zone, 'drop', event => this._onDrop(event), {})
        listen(this, zone, 'click', event => {
            if (! event.target.closest('label') && ! this.input?.disabled) this.input?.click()
        }, {})

        listen(this, this.$el, 'livewire-upload-start', () => this._start(), {})
        listen(this, this.$el, 'livewire-upload-progress', event => this._progress(event.detail?.progress ?? 0), {})
        for (const type of ['livewire-upload-finish', 'livewire-upload-error', 'livewire-upload-cancel']) {
            listen(this, this.$el, type, () => this._end(), {})
        }

    },
    destroy() {
        disconnected(this)
        this._revoke()

        disconnected(this)
    },
    get input() {
        return this.$el.querySelector('[data-slot="file-upload-input"]')
    },

    get dropzone() {
        return this.input?.closest('[data-slot="file-upload-dropzone"], [data-slot="image-upload-dropzone"]')
    },

    _onDrag(event) {
        if (this.input?.disabled || ! event.dataTransfer?.types?.includes('Files')) return

        event.preventDefault()
        event.dataTransfer.dropEffect = 'copy'
        this.dropzone.setAttribute('data-dragging', '')
    },

    _onDrop(event) {
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
        emit(input, 'change')
    },

    _start() {
        const files = [...(this.input?.files ?? [])]

        this.$el.setAttribute('data-uploading', '')
        this._revoke()

        // Image Upload: show the new picture straight away.
        const image = this.$el.querySelector('[data-slot="image-upload-image"]')

        if (image && files[0]?.type.startsWith('image/')) {
            image.src = this._url(files[0])
            image.hidden = false
            this.$el.querySelector('[data-slot="image-upload-placeholder"]')?.setAttribute('hidden', '')
        }

        // File Upload: a chip per file, with the progress.
        const pending = this.$el.querySelector('[data-file-upload-pending]')

        if (pending) {
            pending.replaceChildren(...files.flatMap(file => this._chip(file)))
            pending.closest('[data-file-upload-list]')?.removeAttribute('hidden')
        }

        this.$el.querySelector('[data-file-upload-progress]')?.removeAttribute('hidden')
        this._progress(0)
    },

    _progress(percent) {
        percent = Math.max(0, Math.min(100, Number(percent) || 0))

        const label = (this.$el.dataset.uploadingLabel ?? '').replace(':progress', percent)
        const progress = this.$el.querySelector('[data-file-upload-progress]')

        if (progress) {
            progress.setAttribute('aria-valuenow', percent)
            progress.setAttribute('aria-valuetext', percent + '%')
            progress.setAttribute('aria-label', label)
            progress.toggleAttribute('data-complete', percent === 100)
            progress.querySelector('[data-slot="progress-indicator"]').style.width = percent + '%'
        }

        this.$el.querySelectorAll('[data-file-upload-pending] [data-slot="attachment-description"]').forEach(description => (description.textContent = label))
    },

    _end() {
        this.$el.removeAttribute('data-uploading')
        this.$el.querySelector('[data-file-upload-progress]')?.setAttribute('hidden', '')

        // Livewire's re-render brings the uploaded files; drop the stand-ins once it has.
        queueMicrotask(() => {
            const pending = this.$el.querySelector('[data-file-upload-pending]')

            pending?.replaceChildren()

            const list = pending?.closest('[data-file-upload-list]')

            if (list && ! list.querySelector('[data-slot="attachment"]')) list.setAttribute('hidden', '')
        })
    },

    _chip(file) {
        const image = file.type.startsWith('image/')
        const chip = clone(this._templates[image ? 'pending-image' : 'pending'])

        if (! chip) return []

        if (image) chip.querySelector('img').src = this._url(file)
        chip.querySelector('[data-slot="attachment-title"]').textContent = file.name
        chip.querySelector('[data-slot="attachment-description"]').textContent = formatSize(file.size)

        return [chip]
    },

    _url(file) {
        const url = URL.createObjectURL(file)

        this._urls.push(url)

        return url
    },

    _revoke() {
        this._urls.forEach(url => URL.revokeObjectURL(url))
        this._urls = []
    },
})
