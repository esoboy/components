<?php

namespace Esoboy\Ui;

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
        $this->app->singleton(Assets::class, fn () => new Assets(__DIR__.'/../dist'));
        $this->app->singleton(UiManager::class);
        $this->app->scoped(Overlay::class);
        $this->app->scoped(PendingControl::class);
        $this->app->scoped(Sequence::class);
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'ui');

        $this->bootAssets();
        $this->bootTranslations();
        $this->bootFieldContext();
        $this->bootLivewire();

        // The browser writes the sidebar state cookie, so it can't be encrypted.
        EncryptCookies::except(SidebarState::COOKIE);
    }

    /**
     * @uiScripts loads the bundled JavaScript; the asset route serves it until it is
     * published. laravel-assets is the group new Laravel apps republish on composer update.
     */
    protected function bootAssets(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/assets.php');

        Blade::directive('uiScripts', fn (string $expression) => "<?php echo app(\Esoboy\Ui\Assets::class)->scripts({$expression}); ?>");

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/ui'),
            ], ['ui-assets', 'laravel-assets']);
        }
    }

    /**
     * Overlay ids restart with every render of a component, so a morph keeps them.
     */
    protected function bootLivewire(): void
    {
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
