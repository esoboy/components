<?php

use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Livewire\Sanitize;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Sanitize('text')]
    public string $note = '<p>Plain text only: paragraphs and line breaks.</p>';

    #[Sanitize('basic')]
    public string $intro = '<h2>Basic</h2><p style="text-indent:2em;line-height:1.75">Text and its layout: <strong>marks</strong>, <span style="color:#dc2626">colors</span>, <mark style="background-color:#fef08a;color:#171717">highlights</mark>, <span style="font-size:20px">sizes</span>, alignment, indents and lists.</p><ul style="list-style-type:none;padding-left:0"><li style="display:flex;align-items:baseline;gap:0.5em"><input type="checkbox" checked disabled><div><p>A task done</p></div></li><li style="display:flex;align-items:baseline;gap:0.5em"><input type="checkbox" disabled><div><p>A task to do</p></div></li></ul>';

    #[Sanitize]
    #[Validate('required')]
    public string $article = '<h1>Full</h1><p>Everything basic has, and media, embeds, tables, collapsible blocks, formulas like <math><semantics><mrow><msup><mi>e</mi><mrow><mi>i</mi><mi>π</mi></mrow></msup><mo>+</mo><mn>1</mn><mo>=</mo><mn>0</mn></mrow><annotation encoding="application/x-tex">e^{i\pi} + 1 = 0</annotation></semantics></math> and code.</p><img src="/demo/landscape.jpg" alt="A landscape" width="1200" height="800" style="display:block;width:50%;max-width:100%;height:auto;margin-left:auto;margin-right:auto"><details><summary>Click to expand</summary><div><p>Hidden until opened.</p></div></details><pre><code>composer require esoboy/ui</code></pre>';

    public ?string $published = null;

    /** The app's attachment library, as the picker lists it. */
    public array $library = [
        ['url' => '/demo/landscape.jpg', 'name' => 'landscape.jpg', 'mime' => 'image/jpeg', 'size' => 56814, 'alt' => 'A landscape', 'width' => 1200, 'height' => 800],
        ['url' => '/demo/photo-1.svg', 'name' => 'photo-1.svg', 'mime' => 'image/svg+xml', 'size' => 438, 'alt' => 'First photo'],
        ['url' => '/demo/photo-2.svg', 'name' => 'photo-2.svg', 'mime' => 'image/svg+xml', 'size' => 436, 'alt' => 'Second photo'],
        ['url' => '/demo/tone.wav', 'name' => 'tone.wav', 'mime' => 'audio/wav', 'size' => 32044],
        ['url' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4', 'name' => 'flower.mp4', 'mime' => 'video/mp4', 'size' => 1036657],
        ['url' => '/demo/guide.pdf', 'name' => 'guide.pdf', 'mime' => 'application/pdf', 'size' => 389],
    ];

    /** @var list<string> */
    public array $picked = [];

    /** @var list<string> */
    public array $accept = [];

    public array $drops = [];

    // editor-media without files: the picker opens, filtered to what the editor takes.
    public function pickMedia(array $accept): void
    {
        $this->accept = $accept;
        $this->picked = [];
        Ui::dialog('library')->open();
    }

    public function insertPicked(): void
    {
        Ui::editor('article')->insert(array_values(array_intersect_key($this->library, array_flip($this->picked))));
        Ui::dialog('library')->close();
    }

    public function cancelPick(): void
    {
        Ui::editor('article')->insert([]);
    }

    // editor-media with files dropped or pasted: uploaded, then inserted.
    public function insertDrops(): void
    {
        Ui::editor('article')->insert(collect($this->drops)->map(fn ($file) => [
            'url' => $file->temporaryUrl(),
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ])->all());

        $this->drops = [];
    }

    public function kind(array $attachment): string
    {
        return in_array(strtok($attachment['mime'], '/'), ['image', 'audio', 'video'], true) ? strtok($attachment['mime'], '/') : 'file';
    }

    public function publish(): void
    {
        $this->validate();

        $this->published = $this->article;
    }
};
?>

<div class="mx-auto max-w-3xl space-y-10 p-10">
    <section class="space-y-2">
        <h2 class="text-lg font-semibold">format="text"</h2>
        <x-ui::editor wire:model="note" format="text" :max-length="200" placeholder="Just text…" />
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold">format="basic"</h2>
        <x-ui::editor wire:model="intro" format="basic" />
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold">A list of features</h2>
        <x-ui::editor :format="['bold', 'italic', 'link', 'emoji']" :tools="['bubble-menu', 'character-count']" :max-length="140" placeholder="Bold, italic, links and emoji; select text for its menu, type : for emoji" />
    </section>

    <form wire:submit="publish" class="space-y-4">
        <x-ui::field>
            <x-ui::field.label>format="full"</x-ui::field.label>
            <x-ui::editor id="article" wire:model="article"
                x-on:editor-media="$event.detail.files.length ? $wire.uploadMultiple('drops', $event.detail.files, () => $wire.insertDrops()) : $wire.pickMedia($event.detail.accept)" />
            <x-ui::field.description>The media button asks the app (editor-media): here a library picker in a dialog, many at once; files dropped or pasted are uploaded first. Type / for blocks.</x-ui::field.description>
        </x-ui::field>
        <x-ui::button type="submit">Publish</x-ui::button>
    </form>

    @if ($published)
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">&lt;x-ui::rich-text&gt;</h2>
        <x-ui::rich-text id="published" :html="$published" class="rounded-lg border p-4" />
        <pre class="overflow-x-auto rounded-lg bg-muted p-3 text-xs whitespace-pre-wrap">{{ $published }}</pre>
    </section>
    @endif

    <x-ui::dialog name="library">
        <x-ui::dialog.content x-on:close="$wire.cancelPick()">
            <x-ui::dialog.header>
                <x-ui::dialog.title>Attachment library</x-ui::dialog.title>
                <x-ui::dialog.description>Pick any number; the editor takes: {{ implode(', ', $accept) }}.</x-ui::dialog.description>
            </x-ui::dialog.header>
            <div class="grid gap-2">
                @foreach ($library as $index => $attachment)
                @if (in_array($this->kind($attachment), $accept, true) || in_array('file', $accept, true))
                <x-ui::label class="flex items-center gap-2 rounded-md border p-2">
                    <x-ui::checkbox wire:model="picked" value="{{ $index }}" />
                    <span class="flex-1">{{ $attachment['name'] }}</span>
                    <x-ui::badge variant="secondary">{{ $this->kind($attachment) }}</x-ui::badge>
                </x-ui::label>
                @endif
                @endforeach
            </div>
            <x-ui::dialog.footer>
                <x-ui::dialog.close variant="outline">Cancel</x-ui::dialog.close>
                <x-ui::button wire:click="insertPicked">Insert</x-ui::button>
            </x-ui::dialog.footer>
        </x-ui::dialog.content>
    </x-ui::dialog>
</div>
