/*! Tiptap UI Components | Copyright (c) 2025 Tiptap | MIT License
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software. THE SOFTWARE IS PROVIDED "AS IS", WITHOUT
 * WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT
 * SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
 * OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
// Adapted from ueberdosis/tiptap-ui-components at 799929b.
import * as React from 'react'
import { parseShortcutKeys } from './helpers'

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    tooltip?: string
    shortcutKeys?: string
}
export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ children, className = '', tooltip, shortcutKeys, title, ...props }, ref) => (
        <button
            ref={ref}
            type="button"
            className={`tiptap-button ${className}`}
            data-style="ghost"
            title={
                title ??
                [tooltip, shortcutKeys && parseShortcutKeys({ shortcutKeys }).join('+')]
                    .filter(Boolean)
                    .join(' · ')
            }
            onMouseDown={(event) => event.preventDefault()}
            {...props}
        >
            {children}
        </button>
    ),
)
export const Badge = ({ children }: { children: React.ReactNode }) => <kbd>{children}</kbd>

export function Toolbar({
    children,
    variant = 'fixed',
    className = '',
    ...props
}: React.HTMLAttributes<HTMLDivElement> & { variant?: 'fixed' | 'floating' }) {
    const ref = React.useRef<HTMLDivElement>(null)
    React.useEffect(() => {
        const bar = ref.current!
        const controls = () =>
            Array.from(
                bar.querySelectorAll<HTMLButtonElement>(
                    'button:not(:disabled),select:not(:disabled),input[type="color"]:not(:disabled)',
                ),
            ).filter(
                (el) => !el.closest('[role="dialog"]') && el.closest('[role="toolbar"]') === bar,
            )
        let current: HTMLElement | undefined
        const sync = () => {
            const items = controls()
            if (!current || !items.includes(current as HTMLButtonElement)) current = items[0]
            for (const item of items) item.tabIndex = item === current ? 0 : -1
        }
        const focus = (event: FocusEvent) => {
            if (controls().includes(event.target as HTMLButtonElement)) {
                current = event.target as HTMLElement
                sync()
            }
        }
        const keydown = (event: KeyboardEvent) => {
            const items = controls()
            const index = items.indexOf(event.target as HTMLButtonElement)
            if (
                index < 0 ||
                (event.target instanceof HTMLSelectElement && ['Home', 'End'].includes(event.key))
            )
                return
            const positions: Record<string, number> = {
                ArrowLeft: index - 1,
                ArrowRight: index + 1,
                Home: 0,
                End: items.length - 1,
            }
            const offset = positions[event.key]
            if (offset === undefined) return
            event.preventDefault()
            items[(offset + items.length) % items.length]?.focus()
        }
        sync()
        const changes = new MutationObserver(sync)
        changes.observe(bar, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['disabled'],
        })
        bar.addEventListener('focusin', focus)
        bar.addEventListener('keydown', keydown)
        return () => {
            changes.disconnect()
            bar.removeEventListener('focusin', focus)
            bar.removeEventListener('keydown', keydown)
        }
    }, [])
    return (
        <div
            ref={ref}
            role="toolbar"
            data-variant={variant}
            className={`tiptap-toolbar ${className}`}
            {...props}
        >
            {children}
        </div>
    )
}
export function ToolbarGroup({
    children,
    className = '',
    ...props
}: React.HTMLAttributes<HTMLDivElement>) {
    return (
        <div role="group" className={`tiptap-toolbar-group ${className}`.trim()} {...props}>
            {children}
        </div>
    )
}
export const ToolbarSeparator = () => (
    <div className="tiptap-separator" data-orientation="vertical" role="none" />
)
