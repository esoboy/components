<?php

namespace App\Editor;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Stores the demo's attachments by content so identical files share an address.
 */
final class DemoUploader
{
    public function store(UploadedFile $file): array
    {
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Files up to 20 MB only.']);
        }

        $directory = public_path('demo/uploads');
        $name = sha1_file($file->getRealPath()).'.'.($file->guessExtension() ?? 'bin');

        File::ensureDirectoryExists($directory);
        File::copy($file->getRealPath(), "{$directory}/{$name}");

        [$width, $height] = @getimagesize("{$directory}/{$name}") ?: [null, null];

        return [
            'url' => "/demo/uploads/{$name}",
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }
}
