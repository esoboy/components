<?php

namespace App\Editor;

use Esoboy\Ui\Support\RemoteImage;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DemoMediaUploadController
{
    public function __invoke(Request $request, DemoUploader $uploader): JsonResponse
    {
        $input = $request->validate([
            'source' => ['required', Rule::in(['drop', 'paste', 'data', 'external'])],
            'file' => ['required_unless:source,external', 'nullable', 'file', 'max:20480'],
            'url' => ['required_if:source,external', 'nullable', 'string', 'max:4096'],
        ]);

        $downloaded = $input['source'] === 'external' ? RemoteImage::download($input['url']) : null;
        $file = $input['source'] === 'external' ? $downloaded : ($input['file'] ?? null);

        try {
            if (! $file || ($input['source'] === 'data' && ! in_array((new finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()), RemoteImage::MIME_TYPES, true))) {
                throw ValidationException::withMessages(['file' => __('ui::editor.image-unreadable')]);
            }

            return response()->json(['attachment' => $uploader->store($file)]);
        } finally {
            if ($downloaded) {
                @unlink($downloaded->getPathname());
            }
        }
    }
}
