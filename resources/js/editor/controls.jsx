import React, { useEffect, useId, useLayoutEffect, useRef, useState } from 'react'
import { useTranslate } from './i18n'
import { autoUpdate, computePosition, flip, offset, shift } from '@floating-ui/dom'
import { ChevronDown, X } from 'lucide-react'
import { Button } from './tiptap-ui/primitives'

export function Tool({ icon: Icon, label, active, children, ...props }) {
    return (
        <Button
            aria-label={label}
            tooltip={label}
            aria-pressed={active}
            data-active-state={active == null ? undefined : active ? 'on' : 'off'}
            {...props}
        >
            {Icon && <Icon size={16} aria-hidden="true" />}
            {children}
        </Button>
    )
}

export function Popover({
    label,
    icon,
    text,
    disabled,
    active,
    chevron = true,
    children,
    className = '',
}) {
    const [open, setOpen] = useState(false)
    const ref = useRef(null),
        trigger = useRef(null),
        panel = useRef(null)
    const keyboardOpen = useRef(false)
    const id = useId()
    function close(restore = false) {
        setOpen(false)
        if (restore) trigger.current?.focus()
    }
    useLayoutEffect(() => {
        if (!open) return
        const element = panel.current
        let mounted = true
        let firstPosition = true
        const stop = autoUpdate(trigger.current, element, async () => {
            const { x, y } = await computePosition(trigger.current, element, {
                placement: 'bottom-start',
                strategy: 'fixed',
                middleware: [offset(6), flip(), shift({ padding: 8 })],
            })
            if (!mounted) return
            Object.assign(element.style, {
                left: `${x}px`,
                top: `${y}px`,
                visibility: 'visible',
            })
            if (firstPosition) {
                const focus =
                    element.querySelector('[data-autofocus]') ??
                    (keyboardOpen.current
                        ? element.querySelector(
                              'button:not(:disabled):not([tabindex="-1"]),select,input',
                          )
                        : null)
                focus?.focus({ preventScroll: true })
                firstPosition = false
            }
        })
        const outside = (event) => {
            if (!event.composedPath().includes(ref.current)) close()
        }
        document.addEventListener('pointerdown', outside, true)
        document.addEventListener('focusin', outside, true)
        const host = element.closest('esoboy-editor')
        const reset = () => close()
        host.addEventListener('editor-document-replaced', reset)
        host.addEventListener('editor-state', reset)
        return () => {
            mounted = false
            stop()
            document.removeEventListener('pointerdown', outside, true)
            document.removeEventListener('focusin', outside, true)
            host.removeEventListener('editor-document-replaced', reset)
            host.removeEventListener('editor-state', reset)
        }
    }, [open])
    const Icon = icon
    return (
        <div
            className="popover-anchor"
            ref={ref}
            onKeyDown={(event) => {
                if (open && event.key === 'Escape') {
                    event.stopPropagation()
                    close(true)
                }
                if (event.key === 'ArrowDown' && event.target === trigger.current) {
                    event.preventDefault()
                    keyboardOpen.current = true
                    setOpen(true)
                    if (open)
                        panel.current
                            ?.querySelector(
                                'input,button:not(:disabled):not([tabindex="-1"]),select',
                            )
                            ?.focus()
                }
            }}
        >
            <Button
                ref={trigger}
                aria-label={label}
                tooltip={label}
                disabled={disabled}
                aria-haspopup="dialog"
                aria-expanded={open}
                aria-pressed={active}
                data-active-state={active ? 'on' : 'off'}
                aria-controls={open ? id : undefined}
                onClick={(event) => {
                    keyboardOpen.current = event.detail === 0
                    setOpen(!open)
                }}
            >
                {Icon && <Icon size={16} aria-hidden="true" />}
                {text}
                {chevron && <ChevronDown size={11} aria-hidden="true" />}
            </Button>
            {open && (
                <div
                    ref={panel}
                    className={`popover ${className}`}
                    role="dialog"
                    id={id}
                    aria-label={label}
                >
                    {children(close)}
                </div>
            )}
        </div>
    )
}

export function Dialog({ title, close, children, icon: Icon, className }) {
    const t = useTranslate()
    const ref = useRef(null)
    useEffect(() => {
        const dialog = ref.current
        dialog.showModal()
        dialog.querySelector('[data-autofocus]')?.focus({ preventScroll: true })
        return () => dialog.close()
    }, [])
    return (
        <dialog
            ref={ref}
            className={className}
            aria-label={title}
            onCancel={(event) => {
                event.preventDefault()
                close()
            }}
            onClick={(event) => {
                if (event.target === ref.current) {
                    const bounds = ref.current.getBoundingClientRect()
                    if (
                        event.clientX < bounds.left ||
                        event.clientX > bounds.right ||
                        event.clientY < bounds.top ||
                        event.clientY > bounds.bottom
                    )
                        close()
                }
            }}
        >
            <header>
                {Icon && <Icon size={19} aria-hidden="true" />}
                <strong>{title}</strong>
                <Tool label={t('close')} icon={X} onClick={close} />
            </header>
            {children}
        </dialog>
    )
}
export function Field({ label, children, ...props }) {
    return (
        <label className="field">
            <span>{label}</span>
            {children ?? <input {...props} />}
        </label>
    )
}
