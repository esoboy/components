export function code() {
    return {
        copied: false,
        timer: null,
        async copy(value = null) {
            const content = this.$root.closest('.ui-code-block')?.querySelector('[data-ui-code]')
            const lines = [...(content?.querySelectorAll('.ui-code-block-line-content') ?? [])]
            await navigator.clipboard.writeText(
                value ??
                    (lines.length
                        ? lines
                              .map((line) => (line.textContent === '\n' ? '' : line.textContent))
                              .join('\n')
                        : (content?.textContent ?? '')),
            )
            this.copied = true
            clearTimeout(this.timer)
            this.timer = setTimeout(() => (this.copied = false), 2000)
        },
        destroy() {
            clearTimeout(this.timer)
        },
    }
}
