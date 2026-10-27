<?php

namespace Esoboy\Ui;

use Esoboy\Ui\Livewire\DialogHandle;
use Esoboy\Ui\Support\FieldContext;
use Esoboy\Ui\Support\Overlay;
use Esoboy\Ui\Support\PendingControl;
use Esoboy\Ui\Support\Sequence;
use Esoboy\Ui\Support\SidebarState;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;

use function Livewire\on;

class UiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Overlay::class);
        $this->app->scoped(PendingControl::class);
        $this->app->scoped(Sequence::class);
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'ui');

        $this->bootTranslations();
        $this->bootFieldContext();
        $this->bootLivewire();

        // The browser writes the sidebar state cookie, so it can't be encrypted.
        EncryptCookies::except(SidebarState::COOKIE);
    }

    /**
     * $this->toast(...) and $this->dialog('name') on every Livewire component.
     */
    protected function bootLivewire(): void
    {
        // type: default, success, info, warning, error or loading. An action button calls
        // a method on this component; a toast with the id of a shown one replaces it.
        Component::macro('toast', function (string $message, ?string $description = null, string $type = 'default', ?int $duration = null, ?array $action = null, ?string $id = null) {
            /** @var Component $this */
            $this->dispatch(
                'ui-toast',
                message: $message,
                description: $description,
                type: $type,
                duration: $duration,
                action: $action === null ? null : ['component' => $this->getId(), 'params' => [], ...$action],
                id: $id,
            );
        });

        Component::macro('dialog', function (string $name) {
            /** @var Component $this */
            return new DialogHandle($this, $name);
        });

        on('render', fn (Component $component) => app(Overlay::class)->restart($component->getId()));
    }

    /**
     * Mark field tags before Blaze compiles them; see FieldContext. Blaze hooks the
     * same pipeline from an app()->booted() callback, so registering here runs first.
     */
    protected function bootFieldContext(): void
    {
        $this->app['blade.compiler']->prepareStringsForCompilationUsing(
            fn (string $template) => FieldContext::mark($template),
        );
    }

    /**
     * Package strings live in lang/{locale}.json under "ui.*" keys. An app overrides
     * them in lang/vendor/ui/{locale}.json, which loads after the package's own files.
     */
    protected function bootTranslations(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        $this->loadJsonTranslationsFrom($this->app->langPath('vendor/ui'));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/ui'),
            ], 'ui-lang');
        }
    }
}
