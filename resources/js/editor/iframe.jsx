import React, { useState } from 'react'
import { useTranslate } from './i18n'
import { Node } from '@tiptap/core'
import { closeHistory } from '@tiptap/pm/history'
import { NodeSelection } from '@tiptap/pm/state'
import { NodeViewWrapper, ReactNodeViewRenderer } from '@tiptap/react'
import { AppWindow, PencilLine, Trash } from 'lucide-react'
import { Dialog, Field, Tool } from './controls'
import { frameAttributes, readFrame } from './frame-attributes'
import contract from '../../content/schema.json'

function FrameView({ editor, node, getPos }) {
    const t = useTranslate()
    const attrs = frameAttributes(node.attrs)
    if (!attrs) return <NodeViewWrapper />
    const title = attrs.title ?? t('embeddedContent')
    return (
        <NodeViewWrapper className="embed-node" contentEditable={false}>
            <button
                type="button"
                className="embed-caption"
                aria-label={t('selectEmbed', { title })}
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => editor.chain().focus().setNodeSelection(getPos()).run()}
            >
                <AppWindow size={15} aria-hidden="true" />
                {title}
            </button>
            <iframe
                src={attrs.src}
                title={title}
                width={attrs.width}
                height={attrs.height}
                sandbox={attrs.sandbox}
                allow={attrs.allow}
                loading={attrs.loading}
                allowFullScreen
                referrerPolicy={attrs.referrerpolicy}
            />
        </NodeViewWrapper>
    )
}

export const Iframe = Node.create({
    name: 'iframe',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        const { width, height } = contract.iframe
        return {
            src: { default: null },
            title: { default: null },
            width: { default: width },
            height: { default: height },
        }
    },
    parseHTML() {
        return [{ tag: 'iframe[src]', getAttrs: (el) => readFrame(el) || false }]
    },
    renderHTML({ node }) {
        const attrs = frameAttributes(node.attrs)
        return attrs ? ['iframe', attrs] : ['div']
    },
    addNodeView() {
        return ReactNodeViewRenderer(FrameView)
    },
})

function startEmbedAction(editor) {
    return editor
        .chain()
        .focus()
        .command(({ tr }) => {
            closeHistory(tr)
            return true
        })
}

export function EmbedTools({ editor, open }) {
    const t = useTranslate()
    return (
        <>
            <Tool label={t('editEmbed')} icon={PencilLine} onClick={() => open('editEmbed')} />
            <Tool
                label={t('deleteEmbed')}
                icon={Trash}
                onClick={() => startEmbedAction(editor).deleteSelection().run()}
            />
        </>
    )
}

export function EmbedDialog({ editor, edit, close }) {
    const t = useTranslate()
    const [error, setError] = useState('')
    const current = edit ? editor.getAttributes('iframe') : {}
    function submit(event) {
        event.preventDefault()
        if (!editor.isEditable) return close()
        const data = new FormData(event.currentTarget)
        const input = data.get('source').trim()
        let attrs
        if (input.startsWith('<')) {
            const template = document.createElement('template')
            template.innerHTML = input
            const frame = template.content.querySelector('iframe')
            attrs = frame && readFrame(frame)
        } else {
            attrs = frameAttributes({ ...current, src: input })
        }
        if (!attrs) return setError(t('invalidEmbed'))
        const title = data.get('title').trim() || attrs.title
        const values = { src: attrs.src, title, width: attrs.width, height: attrs.height }
        const { selection } = editor.state
        if (edit && selection.node?.type.name !== 'iframe') return close()
        const chain = startEmbedAction(editor)
        if (edit) chain.updateAttributes('iframe', values)
        else {
            const from = selection instanceof NodeSelection ? selection.to : selection.from
            chain.insertContentAt({ from, to: selection.to }, { type: 'iframe', attrs: values })
        }
        if (chain.run()) close()
        else setError(t('cannotInsertEmbed'))
    }
    return (
        <Dialog title={edit ? t('editEmbed') : t('embed')} icon={AppWindow} close={close}>
            <form onSubmit={submit}>
                <Field label={t('embedSource')}>
                    <textarea
                        name="source"
                        rows={3}
                        data-autofocus
                        required
                        spellCheck={false}
                        placeholder={t('embedPlaceholder')}
                        defaultValue={current.src ?? ''}
                    />
                </Field>
                <Field label={t('title')} name="title" defaultValue={current.title ?? ''} />
                {error && (
                    <p className="error" role="alert">
                        {error}
                    </p>
                )}
                <div className="actions">
                    <button type="button" onClick={close}>
                        {t('cancel')}
                    </button>
                    <button type="submit" className="primary">
                        {edit ? t('save') : t('insert')}
                    </button>
                </div>
            </form>
        </Dialog>
    )
}
