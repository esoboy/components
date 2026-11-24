import { disclosure } from '../behaviors/disclosure.js'

const transition = {
    open(panel) {
        panel.style.setProperty('--panel-height', panel.scrollHeight + 'px')
    },
    close(panel, current) {
        this.$nextTick(() => {
            if (!current()) return
            panel.style.display = 'block'
            panel.style.height = panel.scrollHeight + 'px'
            panel.style.transition = 'none'
            getComputedStyle(panel).height
            requestAnimationFrame(() => {
                if (!current()) return
                panel.style.removeProperty('transition')
                panel.style.removeProperty('height')
                panel.style.display = 'none'
            })
        })
    },
}

export function traceDisclosure(initial) {
    return disclosure(initial, transition)
}
