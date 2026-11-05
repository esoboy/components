import boundValue from './value.js'
import { emit } from './interaction.js'

export function numberInput(binding) {
    return Object.defineProperties(boundValue(binding), Object.getOwnPropertyDescriptors({
        repeat: 0,
        get input() { return this.$el.querySelector('[x-ref="input"]') },
        step(direction) {
            if (this.input.disabled) return
            const step = Number(this.$el.dataset.step) || 1
            const min = this.$el.dataset.min === undefined ? -Infinity : Number(this.$el.dataset.min)
            const max = this.$el.dataset.max === undefined ? Infinity : Number(this.$el.dataset.max)
            const current = this.value === null || this.value === '' ? null : Number(this.value)
            const number = current === null ? (Number.isFinite(min) ? min : 0) : current + direction * step
            this.value = Number(Math.min(max, Math.max(min, number)).toFixed((String(step).split('.')[1] ?? '').length))
            this.render()
            this.changed()
        },
        press(event) {
            const button = event.target.closest?.('[data-number-input]')
            if (! button || button.disabled || event.button !== 0) return
            event.preventDefault()
            const direction = button.dataset.numberInput === 'increment' ? 1 : -1
            this.input.focus()
            this.step(direction)
            this.stop()
            const again = () => { this.step(direction); this.repeat = setTimeout(again, 100) }
            this.repeat = setTimeout(again, 400)
        },
        key(event) {
            if (event.target !== this.input || ! ['ArrowUp', 'ArrowDown'].includes(event.key)) return
            event.preventDefault()
            this.step(event.key === 'ArrowUp' ? 1 : -1)
        },
        render() {
            const value = this.value
            if (! this.input) return
            if (value !== null) this.input.setAttribute('aria-valuenow', String(value))
            else this.input.removeAttribute('aria-valuenow')
            for (const button of this.$el.querySelectorAll('[data-number-input]')) {
                const limit = button.dataset.numberInput === 'increment' ? this.$el.dataset.max : this.$el.dataset.min
                button.disabled = this.input.disabled || (limit !== undefined && value !== null && (button.dataset.numberInput === 'increment' ? Number(value) >= Number(limit) : Number(value) <= Number(limit)))
            }
        },
        stop() { clearTimeout(this.repeat) },
        destroy() { this.stop() },
    }))
}

export function rating(binding) {
    return Object.defineProperties(boundValue(binding), Object.getOwnPropertyDescriptors({
        choose(button) {
            if (! button || button.disabled) return
            const value = Number(button.dataset.value)
            this.value = Number(this.value) === value ? null : value
            this.render()
            this.changed()
        },
        key(event) {
            const buttons = [...this.$el.querySelectorAll('[data-slot="rating-item"]')].filter(button => ! button.disabled)
            const index = buttons.indexOf(event.target)
            const position = { ArrowRight: Math.min(index + 1, buttons.length - 1), ArrowUp: Math.min(index + 1, buttons.length - 1), ArrowLeft: Math.max(0, index - 1), ArrowDown: Math.max(0, index - 1), Home: 0, End: buttons.length - 1 }[event.key]
            if (position === undefined || index < 0) return
            event.preventDefault()
            const button = buttons[position]
            this.value = Number(button.dataset.value)
            this.render()
            button.focus()
            this.changed()
        },
        render() {
            const value = this.value
            const buttons = [...this.$el.querySelectorAll('[data-slot="rating-item"]')]
            const active = Math.max(1, Math.round(Number(value) || 1))
            for (const button of buttons) {
                const number = Number(button.dataset.value)
                button.setAttribute('aria-checked', String(value !== null && number === Math.round(Number(value))))
                button.tabIndex = number === active ? 0 : -1
                button.style.setProperty('--rating-fill', `${value !== null && number <= Number(value) ? 100 : 0}%`)
            }
        },
    }))
}

export function slider(binding) {
    return {
        drag: null,
        get thumbs() { return [...this.$el.querySelectorAll('[data-slot="slider-thumb"]')] },
        paint() {
            const bound = binding.property ? this.$wire.$get(binding.property) : null
            const numbers = bound === null ? this.thumbs.map(thumb => Number(thumb.value)) : (Array.isArray(bound) ? bound : [bound]).map(Number)
            const first = this.thumbs[0]
            if (! first) return
            const min = Number(first.min || 0), max = Number(first.max || 100)
            const percentages = numbers.map(number => max === min ? 0 : (number - min) / (max - min) * 100)
            const range = this.$el.querySelector('[data-slot="slider-range"]')
            range?.style.setProperty('--start', percentages.length > 1 ? Math.min(...percentages) : 0)
            range?.style.setProperty('--end', Math.max(...percentages))
        },
        valueAt(event) {
            const first = this.thumbs[0]
            const rect = this.$el.querySelector('[data-slot="slider-control"]').getBoundingClientRect()
            const vertical = this.$el.dataset.orientation === 'vertical'
            const fraction = Math.min(1, Math.max(0, vertical ? (rect.bottom - event.clientY) / rect.height : (event.clientX - rect.left) / rect.width))
            return Number(first.min || 0) + fraction * (Number(first.max || 100) - Number(first.min || 0))
        },
        press(event) {
            const thumbs = this.thumbs.filter(thumb => ! thumb.disabled)
            if (event.button !== 0 || ! thumbs.length || thumbs.includes(event.target)) return
            const value = this.valueAt(event)
            const thumb = thumbs.reduce((nearest, candidate) => Math.abs(Number(candidate.value) - value) < Math.abs(Number(nearest.value) - value) ? candidate : nearest)
            event.preventDefault()
            this.drag = { thumb, start: thumb.value }
            thumb.focus()
            event.currentTarget.setPointerCapture?.(event.pointerId)
            this.move(event)
        },
        move(event) {
            if (! this.drag) return
            const thumb = this.drag.thumb
            const before = thumb.value
            thumb.value = String(this.valueAt(event))
            if (before !== thumb.value) emit(thumb, 'input')
            this.paint()
        },
        end() {
            if (! this.drag) return
            const { thumb, start } = this.drag
            this.drag = null
            if (thumb.value !== start) emit(thumb, 'change')
        },
        input(event) {
            const thumbs = this.thumbs
            const index = thumbs.indexOf(event.target)
            if (index < 0) return
            const thumb = thumbs[index], before = thumb.value
            if (index > 0 && Number(thumb.value) < Number(thumbs[index - 1].value)) thumb.value = thumbs[index - 1].value
            if (index < thumbs.length - 1 && Number(thumb.value) > Number(thumbs[index + 1].value)) thumb.value = thumbs[index + 1].value
            if (thumb.value !== before) emit(thumb, 'input')
            this.paint()
        },
    }
}

export function otp(binding) {
    return {
        render() {
            if (binding.property) this.$wire.$get(binding.property)
            this.$nextTick(() => {
                const input = this.$el.querySelector('[data-slot="input-otp-input"]')
                if (! input) return
                const focused = document.activeElement === input
                const position = Math.min(input.selectionStart ?? input.value.length, input.maxLength - 1)
                for (const slot of this.$el.querySelectorAll('[data-slot="input-otp-slot"]')) {
                    const index = Number(slot.dataset.index)
                    slot.textContent = input.value[index] ?? ''
                    slot.dataset.active = String(focused && index === position)
                    slot.dataset.caret = String(focused && index === position && input.value[index] === undefined)
                }
            })
        },
    }
}

export function inputCount() {
    return {
        render() {
            const input = this.$el.closest('[data-slot="input-group"]')?.querySelector('[data-slot="input-group-control"]')
            if (! input) return
            const property = input.getAttributeNames().find(name => name.startsWith('wire:model'))
            const value = property ? this.$wire.$get(input.getAttribute(property)) : input.value
            const count = [...String(value ?? '')].length
            const max = Number(this.$el.dataset.max || input.maxLength) > 0 ? Number(this.$el.dataset.max || input.maxLength) : null
            this.$el.textContent = (max ? this.$el.dataset.labelMax : this.$el.dataset.label).replace(':count', count).replace(':max', max)
            this.$el.toggleAttribute('data-over', max !== null && count > max)
            this.$el.id ||= this.$id('input-count')
            const described = (input.getAttribute('aria-describedby') ?? '').split(' ').filter(Boolean)
            if (! described.includes(this.$el.id)) input.setAttribute('aria-describedby', [...described, this.$el.id].join(' '))
        },
    }
}

export function copyCode() {
    return {
        copied: false,
        timer: 0,
        async copy() {
            const block = this.$el.closest('[data-slot="code-block"]')
            const lines = block?.querySelectorAll('[data-slot="code-block-line-content"]') ?? []
            const code = lines.length ? [...lines].map(line => line.textContent === '\n' ? '' : line.textContent).join('\n') : block?.querySelector('[data-slot="code-block-code"]')?.textContent ?? ''
            try {
                await navigator.clipboard.writeText(this.$el.dataset.value ?? code)
                this.copied = true
                const status = this.$el.nextElementSibling
                if (status?.matches('[data-code-block-status]')) status.textContent = this.$el.dataset.labelCopied
                clearTimeout(this.timer)
                this.timer = setTimeout(() => { this.copied = false; if (status?.matches('[data-code-block-status]')) status.textContent = '' }, 2000)
            } catch {}
        },
        destroy() { clearTimeout(this.timer) },
    }
}
