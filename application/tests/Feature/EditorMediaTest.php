<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('stores a pasted attachment through the demo application endpoint', function () {
    $file = UploadedFile::fake()->createWithContent('upload-check.txt', 'Demo editor upload test.');

    $response = $this->post(route('demo.editor.media'), ['source' => 'paste', 'file' => $file], ['Accept' => 'application/json']);
    $path = $response->json('attachment.url');

    try {
        $response->assertOk()
            ->assertJsonPath('attachment.name', 'upload-check.txt')
            ->assertJsonPath('attachment.mime', 'text/plain');
        expect($path)->toStartWith('/demo/uploads/');
        expect(file_get_contents(public_path($path)))->toBe('Demo editor upload test.');
    } finally {
        if (is_string($path) && str_starts_with($path, '/demo/uploads/')) {
            unlink(public_path($path));
        }
    }
});

it('validates the media source and its payload in the application', function () {
    Http::fake();

    $this->postJson(route('demo.editor.media'), ['source' => 'picker'])->assertUnprocessable()->assertJsonValidationErrors('source');
    $this->postJson(route('demo.editor.media'), ['source' => 'drop'])->assertUnprocessable()->assertJsonValidationErrors('file');
    $this->post(route('demo.editor.media'), [
        'source' => 'data',
        'file' => UploadedFile::fake()->createWithContent('fake.png', '<svg onload="x()"/>'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
    $this->postJson(route('demo.editor.media'), [
        'source' => 'external',
        'url' => 'http://127.0.0.1/a.png',
    ])->assertUnprocessable()->assertJsonValidationErrors('file');

    Http::assertNothingSent();
});
