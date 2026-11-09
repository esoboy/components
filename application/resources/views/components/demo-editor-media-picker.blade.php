<div {{ $attributes }}
    data-demo-media-endpoint="{{ route('demo.editor.media') }}"
    data-demo-media-token="{{ csrf_token() }}"
    x-data="{
        localMedia: null,
        uploadingMedia: false,
        requestMedia(request) {
            if (request.source !== 'picker') {
                if (!request.automatic) request.respondWith(this.postMedia(request))
                return
            }
            if (request.kind !== 'audio') {
                this.$wire.openMedia(request)
                return
            }
            if (this.uploadingMedia) {
                this.$wire.cancelAudio(request.requestId)
                return
            }
            this.localMedia = request
            this.$refs.audioPicker.value = ''
            this.$refs.audioPicker.multiple = request.multiple
            this.$refs.audioPicker.click()
        },
        postMedia({ source, file, url, progress }) {
            const body = new FormData()
            body.append('source', source)
            if (file) body.append('file', file)
            if (url) body.append('url', url)

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest()
                xhr.open('POST', this.$root.dataset.demoMediaEndpoint)
                xhr.setRequestHeader('Accept', 'application/json')
                xhr.setRequestHeader('X-CSRF-TOKEN', this.$root.dataset.demoMediaToken)
                xhr.responseType = 'json'
                xhr.upload.onprogress = event => {
                    if (event.lengthComputable) progress?.(event.loaded, event.total)
                }
                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(xhr.response?.attachment)
                    } else {
                        const message = Object.values(xhr.response?.errors ?? {}).flat()[0]
                        reject(new Error(message))
                    }
                }
                xhr.onerror = () => reject(new Error())
                xhr.send(body)
            })
        },
        uploadAudio(event) {
            const request = this.localMedia
            if (!request || !event.target.files.length) return
            this.localMedia = null
            this.uploadingMedia = true
            const fail = async (failed) => {
                try {
                    await this.$wire.cancelAudio(request.requestId, failed)
                } finally {
                    this.uploadingMedia = false
                }
            }
            this.$wire.uploadMultiple('mediaFiles', [...event.target.files], async () => {
                try {
                    await this.$wire.storeAudio(request.requestId)
                } finally {
                    this.uploadingMedia = false
                }
            }, () => fail(true), undefined, () => fail(false))
        },
        cancelAudioPicker() {
            if (this.localMedia) this.$wire.cancelAudio(this.localMedia.requestId)
            this.localMedia = null
        },
    }"
    x-on:ui-editor-media-request="requestMedia($event.detail)"
    x-on:close.capture="
        if ($event.target.id === 'library') {
            const requestId = $event.target.querySelector('[data-library-request]')?.dataset.libraryRequest
            if (requestId) $wire.cancelMedia(requestId)
        }
    "
    x-on:submit.capture="if (uploadingMedia) { $event.preventDefault(); $event.stopImmediatePropagation() }"
>
    <input type="file" accept="audio/*" hidden x-ref="audioPicker" data-demo-audio-picker x-on:change="uploadAudio($event)" x-on:cancel="cancelAudioPicker()">
    {{ $slot }}
</div>
