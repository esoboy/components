<?php

namespace Esoboy\Ui\Facades;

use Esoboy\Ui\UiManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void toast(string $message, ?string $description = null, string $type = 'default', ?int $duration = null, array{label: string, method: string, params?: list<mixed>}|null $action = null, ?string $id = null)
 * @method static \Esoboy\Ui\Livewire\DialogHandle dialog(string $name)
 * @method static \Esoboy\Ui\Livewire\TourHandle tour(string $name)
 * @method static void countdown(string $name = 'verification-code', ?int $seconds = null)
 * @method static string theme(string $scope = 'default')
 * @method static \Esoboy\Ui\Livewire\EditorHandle editor(string $id)
 * @method static string sanitize(?string $html, string|list<string> $format = 'full')
 * @method static string plainText(?string $html, string|list<string> $format = 'full')
 * @method static void editorFormat(string $name, list<string> $features, list<string> $tags = [])
 * @method static void editorWhitelist(array<string, '*'|array<string, '*'|list<string>>> $rules)
 * @method static void embedPlatform(string $name, string $src, ?string $pattern = null, string $still = 'autoplay=0')
 *
 * @see UiManager
 */
final class Ui extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UiManager::class;
    }
}
