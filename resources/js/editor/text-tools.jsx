import React, { useRef, useState } from 'react'
import { useTranslate } from './i18n'
import { ArrowDown, ArrowUp, Link, Unlink, X } from 'lucide-react'
import { Field, Popover, Tool } from './controls'

export function FindPanel({ editor, close }) {
    const t = useTranslate()
    const search = editor.storage.findAndReplace
    const editable = editor.isEditable
    const [term, setTerm] = useState(search.searchTerm)
    const searchInput = useRef(null)
    return (
        <section role="search" className="find-panel" aria-label={t('findAndReplace')}>
            <div className="panel-heading">
                <strong>{t('findAndReplace')}</strong>
                <Tool icon={X} label={t('close')} onClick={close} />
            </div>
            <div className="find-fields">
                <Field
                    label={t('find')}
                    ref={searchInput}
                    value={term}
                    data-autofocus
                    onChange={({ target }) => {
                        setTerm(target.value)
                        editor.commands.setSearchTerm(target.value)
                    }}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' && !event.nativeEvent.isComposing) {
                            event.preventDefault()
                            if (event.shiftKey) editor.commands.goToPreviousResult()
                            else editor.commands.goToNextResult()
                        }
                    }}
                />
                <div className="find-navigation">
                    <output aria-live="polite">
                        {search.results.length ? search.currentIndex + 1 : 0} /{' '}
                        {search.results.length}
                    </output>
                    <Tool
                        icon={ArrowUp}
                        label={t('previousMatch')}
                        disabled={!search.results.length}
                        onClick={() => editor.commands.goToPreviousResult()}
                    />
                    <Tool
                        icon={ArrowDown}
                        label={t('nextMatch')}
                        disabled={!search.results.length}
                        onClick={() => editor.commands.goToNextResult()}
                    />
                </div>
            </div>
            {editable && (
                <Field
                    label={t('replaceWith')}
                    defaultValue={search.replaceTerm}
                    onChange={(event) => editor.commands.setReplaceTerm(event.target.value)}
                />
            )}
            <div className="options">
                {[
                    ['caseSensitive', t('caseSensitive'), 'setCaseSensitive'],
                    ['wholeWord', t('wholeWord'), 'setWholeWord'],
                    ['useRegex', t('useRegex'), 'setUseRegex'],
                ].map(([key, label, command]) => (
                    <label key={key}>
                        <input
                            type="checkbox"
                            checked={search[key]}
                            onChange={(event) => editor.commands[command](event.target.checked)}
                        />{' '}
                        {label}
                    </label>
                ))}
            </div>
            <div className="actions">
                <button
                    type="button"
                    disabled={!term}
                    onClick={() => {
                        setTerm('')
                        editor.commands.clearSearch()
                        searchInput.current.focus()
                    }}
                >
                    {t('clearSearch')}
                </button>
                {editable &&
                    [
                        ['replace', t('replace')],
                        ['replaceAll', t('replaceAll')],
                    ].map(([command, label]) => (
                        <button
                            type="button"
                            key={command}
                            disabled={!search.results.length}
                            onClick={() => {
                                editor.commands[command]()
                                searchInput.current.focus()
                            }}
                        >
                            {label}
                        </button>
                    ))}
            </div>
        </section>
    )
}

// Tiptap UI LinkPopover's set/remove behavior, with the application's target choice.
function LinkForm({ editor, close }) {
    const t = useTranslate()
    const selected = editor.getAttributes('link')
    const [error, setError] = useState('')
    function apply(event) {
        event.preventDefault()
        const data = new FormData(event.currentTarget)
        const href = data.get('href').trim()
        if (
            !href ||
            !['http:', 'https:', 'mailto:', 'tel:'].includes(
                URL.parse(href, document.baseURI)?.protocol,
            )
        ) {
            setError(t('invalidLink'))
            return
        }
        const insertLabel = editor.state.selection.empty && !selected.href
        const attrs = { href, target: data.get('target') || null }
        const chain = editor.chain().focus().extendMarkRange('link').setLink(attrs)
        if (insertLabel)
            chain.insertContent({
                type: 'text',
                text: href,
                marks: [{ type: 'link', attrs }],
            })
        chain.run()
        close()
    }
    return (
        <form onSubmit={apply}>
            <Field
                label={t('linkUrl')}
                name="href"
                data-autofocus
                defaultValue={selected.href ?? ''}
                placeholder={t('urlPlaceholder')}
                required
                autoFocus
            />
            <Field label={t('linkTarget')}>
                <select name="target" defaultValue={selected.target ?? ''}>
                    <option value="">{t('sameWindow')}</option>
                    <option value="_blank">{t('newWindow')}</option>
                </select>
            </Field>
            {error && (
                <p className="error" role="alert">
                    {error}
                </p>
            )}
            <div className="actions">
                {selected.href && (
                    <button
                        type="button"
                        onClick={() => {
                            editor
                                .chain()
                                .focus()
                                .extendMarkRange('link')
                                .unsetLink()
                                .setMeta('preventAutolink', true)
                                .run()
                            close()
                        }}
                    >
                        <Unlink size={15} />
                        {t('removeLink')}
                    </button>
                )}
                <button className="primary" type="submit">
                    {t('apply')}
                </button>
            </div>
        </form>
    )
}

export function LinkPopover({ editor, label }) {
    const t = useTranslate()
    return (
        <Popover
            label={label ?? t('link')}
            icon={Link}
            chevron={false}
            active={editor.isActive('link')}
            disabled={!editor.isEditable}
            className="link-panel"
        >
            {(close) => <LinkForm editor={editor} close={close} />}
        </Popover>
    )
}
