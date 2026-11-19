import { Extension } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Markdown } from '@tiptap/markdown'
import { TextStyleKit } from '@tiptap/extension-text-style'
import TextAlign from '@tiptap/extension-text-align'
import Highlight from '@tiptap/extension-highlight'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import { ListKit } from '@tiptap/extension-list'
import { TableKit } from '@tiptap/extension-table'
import { ContentTable } from './formatting'
import { Details, DetailsSummary, DetailsContent } from '@tiptap/extension-details'
import { MediaImage, MediaAudio, Video, FileAttachment } from './media.js'
import { Iframe } from './iframe'
import { Emoji, emojis } from '@tiptap/extension-emoji'
import { CharacterCount, Placeholder, Focus, Selection } from '@tiptap/extensions'
import FindAndReplace from '@tiptap/extension-find-and-replace'
import Typography from '@tiptap/extension-typography'
import UniqueID from '@tiptap/extension-unique-id'
import TableOfContents from '@tiptap/extension-table-of-contents'
import FileHandler from '@tiptap/extension-file-handler'
import DragHandle from '@tiptap/extension-drag-handle'
import { contract } from './content.js'

const ContentAttributes = Extension.create({
    name: 'contentAttributes',
    addGlobalAttributes() {
        return [
            {
                types: ['orderedList'],
                attributes: {
                    listStyle: {
                        default: null,
                        parseHTML: (el) => el.style.listStyleType || null,
                        renderHTML: (attrs) =>
                            attrs.listStyle ? { style: `list-style-type: ${attrs.listStyle}` } : {},
                    },
                },
            },
        ]
    },
})

export function extensions(root, onOutline, dropFiles, t) {
    const registry = {
        starterKit: StarterKit.configure({
            bulletList: false,
            orderedList: false,
            listItem: false,
            listKeymap: false,
            link: {
                openOnClick: false,
                HTMLAttributes: { rel: 'noopener noreferrer', target: null },
            },
        }),
        textStyleKit: TextStyleKit,
        textAlign: TextAlign.configure({ types: ['heading', 'paragraph'] }),
        highlight: Highlight.configure({ multicolor: true }),
        subscript: Subscript,
        superscript: Superscript,
        listKit: ListKit.configure({
            taskItem: {
                nested: true,
                a11y: {
                    checkboxLabel: (node) =>
                        node.textContent
                            ? t('taskCheckbox', { text: node.textContent })
                            : t('emptyTaskCheckbox'),
                },
            },
        }),
        tableKit: TableKit.configure({ table: false }),
        details: Details.configure({
            persist: true,
            renderToggleButton: ({ element, isOpen }) =>
                element.setAttribute('aria-label', t(isOpen ? 'collapseDetails' : 'expandDetails')),
        }),
        detailsSummary: DetailsSummary,
        detailsContent: DetailsContent,
        image: MediaImage,
        audio: MediaAudio,
        video: Video,
        file: FileAttachment.configure({ fileLabel: () => t('file') }),
        iframe: Iframe,
        emoji: Emoji.extend({ addProseMirrorPlugins: () => [], addInputRules: () => [] }).configure(
            { emojis, enableEmoticons: false },
        ),
        characterCount: CharacterCount,
        findAndReplace: FindAndReplace.configure({ injectCSS: false, searchDebounceMs: 80 }),
        placeholder: Placeholder.configure({ placeholder: () => t('placeholder') }),
        typography: Typography,
        focus: Focus.configure({ className: 'has-focus', mode: 'deepest' }),
        selection: Selection,
        uniqueID: UniqueID.configure({ types: ['heading'] }),
        tableOfContents: TableOfContents.configure({
            onUpdate: onOutline,
            scrollParent: () => root,
        }),
        fileHandler: FileHandler.configure({
            onDrop: (_, files, position) => dropFiles(files, position),
        }),
        dragHandle: DragHandle.configure({
            render: () => {
                const el = document.createElement('button')
                el.type = 'button'
                el.className = 'drag-handle'
                el.style.visibility = 'hidden'
                el.textContent = '⠿'
                el.setAttribute('aria-label', t('dragBlock'))
                return el
            },
        }),
        markdown: Markdown.configure({ markedOptions: { gfm: true } }),
    }
    return [
        ...contract.extensions.map((name) => {
            if (!registry[name]) throw new Error(t('missingCapability', { name }))
            return registry[name]
        }),
        ContentAttributes,
        ContentTable,
    ]
}
