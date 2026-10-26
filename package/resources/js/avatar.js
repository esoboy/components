/*
 * An avatar image sits over its fallback. One that fails to load is hidden so the
 * fallback shows, as shadcn's Avatar does.
 */

const IMAGE = 'img[data-slot="avatar-image"]'

export function initAvatars(win = window) {
    const doc = win.document

    const hideIfBroken = image => {
        if (image.matches?.(IMAGE) && image.complete && image.naturalWidth === 0) image.hidden = true
    }

    // error doesn't bubble; capturing on the document still sees it, new images included.
    doc.addEventListener('error', ({ target }) => target.matches?.(IMAGE) && (target.hidden = true), true)

    // Images that failed before this script ran.
    doc.querySelectorAll(IMAGE).forEach(hideIfBroken)

    // A Livewire morph strips the hidden attribute from an image that already failed.
    doc.addEventListener('livewire:init', () => win.Livewire.hook('morph.updated', ({ el }) => hideIfBroken(el)))
}
