<?php

namespace Esoboy\Ui\Livewire;

use Livewire\Component;

/**
 * Ui::editor('body')->insert($attachments) / ->insertContent($html) / ->focus(), for the
 * Editor with that id.
 */
final class EditorHandle
{
    public function __construct(
        private readonly Component $component,
        private readonly string $id,
    ) {}

    /**
     * Answers the Editor's editor-media event: inserts the attachments where they were
     * asked for, or nothing for none. Each is ['url' => …] with any of name, mime (or
     * type), size (bytes), alt, width, height and poster: images, audio and video show
     * as themselves, the rest (and kinds the format leaves out) as files.
     *
     * @param  array<array<string, mixed>>  $attachments
     */
    public function insert(array $attachments): void
    {
        $this->component->dispatch('ui-editor', id: $this->id, action: 'insert', attachments: array_values($attachments), html: null);
    }

    /**
     * Inserts HTML at the cursor (a template, a reply), as far as the format allows.
     */
    public function insertContent(string $html): void
    {
        $this->component->dispatch('ui-editor', id: $this->id, action: 'insert-content', attachments: [], html: $html);
    }

    public function focus(): void
    {
        $this->component->dispatch('ui-editor', id: $this->id, action: 'focus', attachments: [], html: null);
    }
}
