<?php

namespace Esoboy\Ui\Tests;

use Esoboy\Ui\UiServiceProvider;
use Livewire\Blaze\BlazeServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function tearDown(): void
    {
        Livewire::flushState();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, BlazeServiceProvider::class, UiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('u', 32)));
        $app['config']->set('app.locale', 'en');
    }
}
