<?php

use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate(['attachments.*' => 'file|max:2048'])]
    public array $attachments = [];

    #[Validate(['gallery.*' => 'image|max:4096'])]
    public array $gallery = [];

    #[Validate('nullable|image|max:1024')]
    public $avatar;

    public ?string $saved = null;

    public function updatedAttachments(): void
    {
        $this->validateOnly('attachments.*');
    }

    public function save(): void
    {
        $this->validate();

        $this->saved = count($this->attachments).' files, '.count($this->gallery).' images, avatar: '.($this->avatar ? 'new' : 'unchanged');
        $this->toast('Saved', description: $this->saved, type: 'success');
    }
};
?>

<div class="mx-auto max-w-2xl space-y-10 p-10">
    <form wire:submit="save" class="space-y-8">
        <x-ui::field>
            <x-ui::field.label>Avatar</x-ui::field.label>
            <x-ui::image-upload wire:model="avatar" shape="circle" src="https://github.com/shadcn.png" />
            <x-ui::field.description>PNG or JPG, up to 1 MB.</x-ui::field.description>
        </x-ui::field>

        <x-ui::field>
            <x-ui::field.label>Attachments</x-ui::field.label>
            <x-ui::file-upload wire:model="attachments" multiple description="Any file up to 2 MB" />
        </x-ui::field>

        <x-ui::field>
            <x-ui::field.label>Product images</x-ui::field.label>
            <x-ui::file-upload wire:model="gallery" multiple accept="image/*" orientation="vertical" title="Drop images here" description="PNG, JPG or WebP" />
        </x-ui::field>

        <x-ui::file-upload wire:model="attachments" multiple disabled title="Disabled" />

        <x-ui::button type="submit">Save</x-ui::button>
        <p class="text-sm text-muted-foreground">Saved: <span id="saved">{{ $saved ?? '—' }}</span></p>
    </form>

    <section class="space-y-3">
        <h2 class="text-sm font-medium">Image preview</h2>
        <x-ui::image-preview id="gallery-preview" class="flex flex-wrap gap-2">
            @foreach (range(1, 4) as $photo)
                <x-ui::image-preview.item class="size-20" src="/demo/photo-{{ $photo }}.svg" alt="Photo {{ $photo }}" />
            @endforeach
        </x-ui::image-preview>
        <x-ui::image-preview id="single-preview">
            <x-ui::image-preview.item class="h-24 w-40" src="/demo/photo-4.svg" />
        </x-ui::image-preview>
    </section>
</div>
