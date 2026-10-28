<?php

use Livewire\Attributes\Validate;
use Esoboy\Ui\Facades\Ui;
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

    #[Validate('nullable|image|max:4096')]
    public $photo;

    public ?array $crop = null;

    public ?array $banner = null;

    public ?string $cropped = null;

    public function updatedAttachments(): void
    {
        $this->validateOnly('attachments.*');
    }

    public function save(): void
    {
        $this->validate();

        $this->saved = count($this->attachments).' files, '.count($this->gallery).' images, avatar: '.($this->avatar ? 'new' : 'unchanged');
        Ui::toast('Saved', description: $this->saved, type: 'success');
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');
        $this->reset('crop', 'cropped');
    }

    /**
     * Cuts the crop out of the photo with GD, as an app would before storing it.
     */
    public function cropPhoto(): void
    {
        $this->validate(['crop.x' => 'required|integer|min:0', 'crop.y' => 'required|integer|min:0', 'crop.width' => 'required|integer|min:1', 'crop.height' => 'required|integer|min:1']);

        $source = imagecreatefromstring(file_get_contents($this->photo?->getRealPath() ?? public_path('demo/landscape.jpg')));
        $part = imagecrop($source, $this->crop);

        ob_start();
        imagejpeg(imagescale($part, 256), null, 85);
        $this->cropped = 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
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
            @foreach (range(1, 4) as $number)
                <x-ui::image-preview.item class="size-20" src="/demo/photo-{{ $number }}.svg" alt="Photo {{ $number }}" />
            @endforeach
        </x-ui::image-preview>
        <x-ui::image-preview id="single-preview">
            <x-ui::image-preview.item class="h-24 w-40" src="/demo/photo-4.svg" />
        </x-ui::image-preview>
    </section>

    <section class="space-y-4">
        <h2 class="text-sm font-medium">Cropper</h2>
        <x-ui::field>
            <x-ui::field.label>Photo to crop</x-ui::field.label>
            <x-ui::file-upload wire:model="photo" accept="image/*" title="Drop a photo" description="Or crop the sample landscape below" />
        </x-ui::field>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::cropper id="avatar-cropper" :image="$photo?->temporaryUrl() ?? '/demo/landscape.jpg'" wire:model="crop" class="h-80 max-w-md rounded-lg border">
                <x-ui::cropper.image />
                <x-ui::cropper.crop-area class="rounded-full" />
            </x-ui::cropper>
            <div class="space-y-3">
                <x-ui::button wire:click="cropPhoto">Crop on the server</x-ui::button>
                @if ($cropped)
                <img id="cropped" src="{{ $cropped }}" alt="Cropped" class="size-32 rounded-full border">
                @endif
                <p class="text-xs text-muted-foreground">Crop: <span id="crop-value">{{ $crop ? json_encode($crop) : '—' }}</span></p>
            </div>
        </div>
        <x-ui::cropper id="banner-cropper" image="/demo/landscape.jpg" :aspect-ratio="16 / 9" :max-zoom="4" wire:model.live="banner" class="h-64 rounded-lg border">
            <x-ui::cropper.image />
            <x-ui::cropper.crop-area />
        </x-ui::cropper>
        <p class="text-xs text-muted-foreground">Banner (live): <span id="banner-value">{{ $banner ? json_encode($banner) : '—' }}</span></p>
    </section>
</div>
