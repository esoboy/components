<?php

namespace App\Editor;

use Esoboy\Ui\Contracts\EditorUploader;
use Esoboy\Ui\Support\EditorUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * The demo's uploader (config ui.editor.uploader): files go to public/demo/uploads, named
 * by their content, so the same picture saved twice is one file.
 */
final class DemoUploader implements EditorUploader
{
    public function authorize(string $source, ?string $url, array $context): void
    {
        // The public editor demo allows uploads.
    }

    public function upload(EditorUpload $upload): ?array
    {
        $name = $upload->source === 'external' ? basename((string) parse_url((string) $upload->url, PHP_URL_PATH)) : null;

        return $this->store($upload->file, $name);
    }

    public function store(UploadedFile $file, ?string $originalName = null): array
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
            'name' => $originalName ?? $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }
}
