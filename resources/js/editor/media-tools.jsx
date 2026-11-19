import React, { useState } from 'react'
import { useTranslate } from './i18n'
import {
    Image,
    EighthNote,
    Clapperboard,
    Paperclip,
    Globe,
    FolderOpen,
    PencilLine,
    Replace,
    TextAlignStart,
    TextAlignCenter,
    TextAlignEnd,
    Trash,
    ArrowLeft,
    ChevronRight,
    Scaling,
    Check,
} from 'lucide-react'
import { NodeSelection } from '@tiptap/pm/state'
import { Dialog, Field, Popover, Tool } from './controls'
import {
    applyMediaSize,
    isVisual,
    insertMedia,
    mediaDisplaySizes,
    mediaURL,
    nameFromURL,
} from './media'
import { ToolbarGroup, ToolbarSeparator } from './tiptap-ui/primitives'

export const mediaIcons = { image: Image, audio: EighthNote, video: Clapperboard, file: Paperclip }

const sizeHints = { sm: '25%', md: '50%', lg: '75%', full: '100%' }

function MediaSize({ editor, attrs }) {
    const t = useTranslate()
    const { displaySize, width, height, fileWidth, fileHeight } = attrs
    const natural =
        (width == null && height == null) || (width === fileWidth && height === fileHeight)
    const active = displaySize ?? (natural ? 'original' : null)
    return (
        <Popover
            label={t('mediaSize')}
            icon={Scaling}
            chevron={false}
            active={Boolean(displaySize) || !natural}
            className="action-menu media-size-menu"
        >
            {(close) =>
                mediaDisplaySizes.map((size) => (
                    <button
                        key={size}
                        type="button"
                        aria-label={t(size)}
                        aria-description={
                            sizeHints[size] && t('widthHint', { percent: sizeHints[size] })
                        }
                        aria-pressed={active === size}
                        data-autofocus={active === size || undefined}
                        onClick={() => {
                            close()
                            applyMediaSize(editor, size)
                        }}
                    >
                        <span>{t(size)}</span>
                        <span className="size-hint">{sizeHints[size]}</span>
                        {active === size && <Check size={14} aria-hidden="true" />}
                    </button>
                ))
            }
        </Popover>
    )
}

export function MediaTools({ editor, open }) {
    const t = useTranslate()
    const node = editor.state.selection.node
    const kind = node?.type.name
    if (!mediaIcons[kind]) return null
    const { attrs } = node
    const hasSettings = Boolean(attrs.title || attrs.alt || attrs.loop || attrs.muted)
    return (
        <>
            {isVisual(kind) && (
                <>
                    <MediaSize editor={editor} attrs={attrs} />
                    <ToolbarSeparator />
                    <ToolbarGroup aria-label={t('mediaAlignment')}>
                        {[
                            [TextAlignStart, 'left', t('left')],
                            [TextAlignCenter, 'center', t('center')],
                            [TextAlignEnd, 'right', t('right')],
                        ].map(([Icon, align, label]) => (
                            <Tool
                                key={align}
                                label={label}
                                icon={Icon}
                                active={(attrs.align || 'left') === align}
                                onClick={() =>
                                    editor.chain().focus().updateAttributes(kind, { align }).run()
                                }
                            />
                        ))}
                    </ToolbarGroup>
                    <ToolbarSeparator />
                </>
            )}
            <ToolbarGroup>
                <Tool
                    label={t('mediaSettings')}
                    icon={PencilLine}
                    data-active-state={hasSettings ? 'on' : 'off'}
                    onClick={() => open({ kind, mode: 'settings' })}
                />
                <Tool
                    label={t('replaceFile')}
                    icon={Replace}
                    onClick={() => open({ kind, mode: 'replace' })}
                />
                <Tool
                    label={t('deleteMedia')}
                    icon={Trash}
                    onClick={() => editor.chain().focus().deleteSelection().run()}
                />
            </ToolbarGroup>
        </>
    )
}

export function MediaDialog({ editor, kind, mode, close, selectLocal }) {
    const t = useTranslate()
    const [error, setError] = useState('')
    const [remote, setRemote] = useState(false)
    const settings = mode === 'settings',
        replace = mode === 'replace'
    const current = mode === 'insert' ? {} : editor.getAttributes(kind)
    const label = t(kind)
    function submit(event) {
        event.preventDefault()
        if (!editor.isEditable) return close()
        const data = new FormData(event.currentTarget)
        const alt = data.get('alt'),
            title = data.get('title')
        try {
            if (settings) {
                const attrs = { alt, title }
                if (isVisual(kind)) {
                    attrs.width = Number(data.get('width')) || null
                    attrs.height = Number(data.get('height')) || null
                    if (attrs.width !== current.width || attrs.height !== current.height)
                        attrs.displaySize = null
                }
                if (kind === 'audio')
                    Object.assign(attrs, { loop: data.has('loop'), muted: data.has('muted') })
                editor.chain().focus().updateAttributes(kind, attrs).run()
            } else {
                const src = mediaURL(data.get('url'), t),
                    name = nameFromURL(src)
                const dot = name.lastIndexOf('.')
                const attrs = {
                    src,
                    name,
                    ext: dot > 0 ? name.slice(dot + 1).toLowerCase() : '',
                    alt,
                    title,
                }
                if (replace && isVisual(kind))
                    Object.assign(attrs, { align: current.align, displaySize: current.displaySize })
                const selection = editor.state.selection
                if (replace && selection.node?.type.name !== kind) return close()
                const from =
                    !replace && selection instanceof NodeSelection ? selection.to : selection.from
                if (!insertMedia(editor, { from, to: selection.to }, [{ type: kind, attrs }]))
                    throw new Error(t('cannotInsertFile'))
            }
            close()
        } catch (reason) {
            setError(reason.message)
        }
    }
    return (
        <Dialog
            icon={mediaIcons[kind]}
            title={t(settings ? 'settingsForMedia' : replace ? 'replaceMedia' : 'insertMedia', {
                kind: label,
            })}
            close={close}
        >
            {!settings && !remote ? (
                <div className="media-sources">
                    <button type="button" data-autofocus onClick={() => selectLocal(kind, replace)}>
                        <FolderOpen size={19} aria-hidden="true" />
                        {t('chooseFile')}
                        <ChevronRight size={15} aria-hidden="true" />
                    </button>
                    <button type="button" onClick={() => setRemote(true)}>
                        <Globe size={19} aria-hidden="true" />
                        {t('remoteFile')}
                        <ChevronRight size={15} aria-hidden="true" />
                    </button>
                </div>
            ) : (
                <form onSubmit={submit}>
                    {!settings && (
                        <button
                            type="button"
                            className="source-back"
                            onClick={() => {
                                setRemote(false)
                                setError('')
                            }}
                        >
                            <ArrowLeft size={14} aria-hidden="true" /> {t('chooseSource')}
                        </button>
                    )}
                    {!settings && (
                        <Field
                            label={t('fileUrl')}
                            name="url"
                            placeholder={t('urlPlaceholder')}
                            required
                            autoFocus
                        />
                    )}
                    <Field
                        label={t('title')}
                        name="title"
                        defaultValue={current.title ?? ''}
                        autoFocus={settings}
                    />
                    <Field label={t('altText')} name="alt" defaultValue={current.alt ?? ''} />
                    {settings && isVisual(kind) && (
                        <div className="columns">
                            <Field
                                label={t('widthPx')}
                                name="width"
                                type="number"
                                min="1"
                                defaultValue={current.width ?? ''}
                            />
                            <Field
                                label={t('heightPx')}
                                name="height"
                                type="number"
                                min="1"
                                defaultValue={current.height ?? ''}
                            />
                        </div>
                    )}
                    {settings && kind === 'audio' && (
                        <div className="options">
                            <label>
                                <input type="checkbox" name="loop" defaultChecked={current.loop} />{' '}
                                {t('loop')}
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="muted"
                                    defaultChecked={current.muted}
                                />{' '}
                                {t('muted')}
                            </label>
                        </div>
                    )}
                    {error && (
                        <p role="alert" className="error">
                            {error}
                        </p>
                    )}
                    <div className="actions">
                        <button type="button" onClick={close}>
                            {t('cancel')}
                        </button>
                        <button type="submit" className="primary">
                            {settings
                                ? t('saveSettings')
                                : replace
                                  ? t('replaceFile')
                                  : t('insertMedia', { kind: label })}
                        </button>
                    </div>
                </form>
            )}
        </Dialog>
    )
}
