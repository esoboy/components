<?php

namespace Esoboy\Ui;

use Esoboy\Ui\Livewire\DialogHandle;
use Esoboy\Ui\Livewire\EditorHandle;
use Esoboy\Ui\Livewire\TourHandle;
use Esoboy\Ui\Support\RichText;
use Esoboy\Ui\Support\Theme;
use Livewire\Component;
use LogicException;

/**
 * What Ui:: offers server code. toast(), dialog(), tour(), editor() and countdown() reach
 * the browser through the Livewire component being handled, so they work from its
 * methods, Form objects and actions alike.
 */
final class UiManager
{
    /**
     * Shows a toast. type: default, success, info, warning, error or loading. An action
     * button calls a method on the same component; a toast with the id of a shown one
     * replaces it.
     *
     * @param  array{label: string, method: string, params?: list<mixed>}|null  $action
     */
    public function toast(string $message, ?string $description = null, string $type = 'default', ?int $duration = null, ?array $action = null, ?string $id = null): void
    {
        $component = $this->component(__FUNCTION__);

        $component->dispatch(
            'ui-toast',
            message: $message,
            description: $description,
            type: $type,
            duration: $duration,
            action: $action === null ? null : ['component' => $component->getId(), 'params' => [], ...$action],
            id: $id,
        );
    }

    /**
     * Opens or closes the dialog, sheet or drawer with that name.
     */
    public function dialog(string $name): DialogHandle
    {
        return new DialogHandle($this->component(__FUNCTION__), $name);
    }

    /**
     * Starts or closes the tour with that name.
     */
    public function tour(string $name): TourHandle
    {
        return new TourHandle($this->component(__FUNCTION__), $name);
    }

    /**
     * Starts the Verification Code Buttons of that name; seconds defaults to theirs.
     */
    public function countdown(string $name = 'verification-code', ?int $seconds = null): void
    {
        $this->component(__FUNCTION__)->dispatch('ui-countdown', name: $name, seconds: $seconds);
    }

    /**
     * Inserts into the Editor with that id: the attachments it asked for, or HTML.
     */
    public function editor(string $id): EditorHandle
    {
        return new EditorHandle($this->component(__FUNCTION__), $id);
    }

    /**
     * Cleans HTML to the format's whitelist, before it is stored or shown; #[Sanitize]
     * does this for a Livewire property, Rich Text before it shows.
     *
     * @param  string|list<string>  $format  text, basic, full, a named format or a list of features
     */
    public function sanitize(?string $html, string|array $format = 'full'): string
    {
        return RichText::sanitize($html, $format);
    }

    /**
     * The text of rich text (cleaned the same way first), for search, summaries and
     * length checks.
     *
     * @param  string|list<string>  $format
     */
    public function plainText(?string $html, string|array $format = 'full'): string
    {
        return RichText::plainText($html, $format);
    }

    /**
     * Names a format of features and presets, for format="…", #[Sanitize('…')] and
     * sanitize(), with more for its whitelist: tags and the attributes kept on them
     * (['section' => ['style']]), and CSS properties. Register it in a service
     * provider's boot().
     *
     * @param  list<string>  $features
     * @param  array<string, list<string>>  $allow
     * @param  list<string>  $css
     */
    public function editorFormat(string $name, array $features, array $allow = [], array $css = []): void
    {
        RichText::format($name, $features, $allow, $css);
    }

    /**
     * Lets embeds play from another video platform: share links matching the pattern (a
     * JavaScript regular expression) become the player address, $1 its first capture.
     */
    public function embedPlatform(string $name, string $pattern, string $src): void
    {
        RichText::platform($name, $pattern, $src);
    }

    /**
     * The app's theme choice (light, dark or system), to render theme controls in their state.
     */
    public function theme(string $scope = 'default'): string
    {
        return Theme::current($scope);
    }

    private function component(string $method): Component
    {
        return app('livewire')->current()
            ?: throw new LogicException("Ui::{$method}() reaches the browser through Livewire; call it while a Livewire component is handled.");
    }
}
