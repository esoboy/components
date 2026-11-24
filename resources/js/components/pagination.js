export function pagination(live) {
    return {
        jump() {
            const input = this.$refs.number
            if (!input.reportValidity()) return
            const { pageName, pageUrl } = this.$root.dataset
            if (live) return this.$wire.gotoPage(input.valueAsNumber, pageName)
            const url = new URL(pageUrl, location.href)
            url.searchParams.set(pageName, input.value)
            location.assign(url)
        },
    }
}
