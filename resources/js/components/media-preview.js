export function previewImage() {
    return {
        zoom: 1,
        rotation: 0,
        imageStyle(image) {
            let scale = this.zoom
            if (this.rotation % 180 && image.clientWidth && image.clientHeight) {
                const canvas = image.parentElement
                scale *= Math.min(
                    1,
                    canvas.clientWidth / image.clientHeight,
                    canvas.clientHeight / image.clientWidth,
                )
            }
            return { transform: `scale(${scale}) rotate(${this.rotation}deg)` }
        },
    }
}
