import { createContext, useContext } from 'react'
import english from '../../../lang/en/editor.json'
import chinese from '../../../lang/cn/editor.json'

type Replacements = Record<string, string | number>
export function translator(locale: string, overrides?: Record<string, string>) {
    const language = locale.toLowerCase().split(/[-_]/, 1)[0]
    const catalog = language === 'zh' || language === 'cn' ? chinese : english
    const messages: Record<string, string> = overrides ? { ...catalog, ...overrides } : catalog
    return (key: string, values?: Replacements) => {
        const message = messages[key]
        if (!values) return message
        return message.replace(/:(\w+)/g, (token, name) =>
            Object.hasOwn(values, name) ? String(values[name]) : token,
        )
    }
}

export const TranslationContext = createContext<ReturnType<typeof translator> | null>(null)
export function useTranslate() {
    return useContext(TranslationContext)!
}
