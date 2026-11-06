<div {{ $attributes }}
    x-data="{
        localMedia: null,
        uploadingMedia: false,
        requestMedia(request) {
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
