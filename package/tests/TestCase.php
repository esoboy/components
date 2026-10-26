<?php

namespace Esoboy\Ui\Tests;

use Esoboy\Ui\UiServiceProvider;
use Livewire\Blaze\BlazeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BlazeServiceProvider::class,
            UiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('u', 32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('view:clear');
    }
}
