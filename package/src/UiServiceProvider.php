<?php

namespace Esoboy\Ui;

use Esoboy\Ui\Support\FieldContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'ui');

        $this->bootTranslations();
        $this->bootFieldContext();
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
