<?php

use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Rules\RichTextLength;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public string $note = '<h3>Text</h3><p>Text and its layout: <strong>marks</strong>, <span style="color:#dc2626">colors</span>, <span style="font-size:18px">sizes</span>, alignment, indents and lists; no images.</p>';

    public string $intro = '<h2>Basic</h2><p style="text-indent:2em;line-height:1.75">Everything text has (<strong>marks</strong>, <mark style="background-color:#fef08a;color:#171717">highlights</mark>, <span style="font-size:20px">sizes</span>, …), and images, video, audio, files and emoji 😀.</p><img src="/demo/landscape.jpg" alt="A landscape" width="1200" height="800" style="display:block;width:40%;max-width:100%;height:auto;margin-left:auto;margin-right:auto"><ul style="list-style-type:none;padding-left:0"><li style="display:flex;align-items:baseline;gap:0.5em"><input type="checkbox" checked disabled><div><p>A task done</p></div></li><li style="display:flex;align-items:baseline;gap:0.5em"><input type="checkbox" disabled><div><p>A task to do</p></div></li></ul>';

    #[Validate(['required', new RichTextLength(max: 2000)])]
    public string $article = '<h1>Full</h1><h2>Media</h2><p>Everything basic has, and media, embeds, tables, collapsible blocks, formulas like <math><semantics><mrow><msup><mi>e</mi><mrow><mi>i</mi><mi>π</mi></mrow></msup><mo>+</mo><mn>1</mn><mo>=</mo><mn>0</mn></mrow><annotation encoding="application/x-tex">e^{i\pi} + 1 = 0</annotation></semantics></math> and code.</p><img src="/demo/landscape.jpg" alt="A landscape" width="1200" height="800" style="display:block;width:50%;max-width:100%;height:auto;margin-left:auto;margin-right:auto"><div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:8px;align-items:start"><img src="/demo/photo-1.svg" alt="First photo" style="display:block;width:100%;height:auto"><img src="/demo/photo-2.svg" alt="Second photo" style="display:block;width:100%;height:auto"></div><h2>Blocks</h2><h3>Collapsible</h3><details><summary>Click to expand</summary><div><p>Hidden until opened.</p></div></details><pre><code>composer require esoboy/ui</code></pre><section style="padding:12px 16px;border-left:3px solid #f97316;background-color:rgba(249,115,22,0.08)"><p><strong>A section</strong> pasted from a WeChat article: the whitelist keeps the tag and its style.</p></section><aside style="padding:12px 16px;border-radius:6px;background-color:rgba(59,130,246,0.1);border-left:4px solid #3b82f6"><p>A callout.</p></aside>';

    public ?string $published = null;

    /** The app's own attachment library, inserted with Ui::editor()->insert(). */
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

    public function pickMedia(): void
    {
        $this->picked = [];
        Ui::dialog('library')->open();
    }

    public function insertPicked(): void
    {
        Ui::editor('article')->insert(array_values(array_intersect_key($this->library, array_flip($this->picked))));
        Ui::dialog('library')->close();
    }

    public function kind(array $attachment): string
    {
        return in_array(strtok($attachment['mime'], '/'), ['image', 'audio', 'video'], true) ? strtok($attachment['mime'], '/') : 'file';
    }

    public function publish(): void
    {
        $this->validate();

        $this->published = Ui::sanitize($this->article);
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
        <x-ui::editor :format="['bold', 'italic', 'link', 'emoji', 'bubble-menu', 'character-count']" :max-length="140" placeholder="Bold, italic, links and emoji; select text for its menu, type : for emoji" />
    </section>

    <form wire:submit="publish" class="space-y-4">
        <x-ui::field>
            <x-ui::field.label>format="full"</x-ui::field.label>
            <x-ui::editor wire:model="article" :max-length="2000" :upload-context="['page' => 'editor']" />
            <x-ui::field.description>The image, video, audio and file buttons, and files dropped or pasted, upload with the app's uploader (config ui.editor.uploader, here App\Editor\DemoUploader). Images from other sites and pasted ones are saved from their menu, or all at once from the footer (or as they come in, with ui.editor.auto_upload). Type / for blocks.</x-ui::field.description>
        </x-ui::field>
        <div class="flex gap-2">
            <x-ui::button type="submit">Publish</x-ui::button>
            <x-ui::button type="button" variant="outline" wire:click="pickMedia">Insert from the library</x-ui::button>
        </div>
    </form>

    @if ($published)
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">&lt;x-ui::rich-text&gt; and its &lt;x-ui::rich-text.toc&gt;</h2>
        <div class="grid gap-6 md:grid-cols-[1fr_12rem]">
            <x-ui::rich-text id="published" :html="$published" class="rounded-lg border p-4" />
            <x-ui::rich-text.toc :html="$published" class="md:sticky md:top-4 md:self-start">On this page</x-ui::rich-text.toc>
        </div>
        <pre class="overflow-x-auto rounded-lg bg-muted p-3 text-xs whitespace-pre-wrap">{{ $published }}</pre>
    </section>
    @endif

    <x-ui::dialog name="library">
        <x-ui::dialog.content>
            <x-ui::dialog.header>
                <x-ui::dialog.title>Attachment library</x-ui::dialog.title>
                <x-ui::dialog.description>Pick any number: Ui::editor('article')->insert() puts them at the cursor.</x-ui::dialog.description>
            </x-ui::dialog.header>
            <div class="grid gap-2">
                @foreach ($library as $index => $attachment)
                <x-ui::label class="flex items-center gap-2 rounded-md border p-2">
                    <x-ui::checkbox wire:model="picked" value="{{ $index }}" />
                    <span class="flex-1">{{ $attachment['name'] }}</span>
                    <x-ui::badge variant="secondary">{{ $this->kind($attachment) }}</x-ui::badge>
                </x-ui::label>
                @endforeach
            </div>
            <x-ui::dialog.footer>
                <x-ui::dialog.close variant="outline">Cancel</x-ui::dialog.close>
                <x-ui::button wire:click="insertPicked">Insert</x-ui::button>
            </x-ui::dialog.footer>
        </x-ui::dialog.content>
    </x-ui::dialog>
</div>
