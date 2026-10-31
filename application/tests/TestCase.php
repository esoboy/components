<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static bool $published = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Pest's browser server trims the bodies of file responses, which breaks the package
        // route's precompressed scripts: serve them as published files, as production does,
        // from the build being tested.
        if (! self::$published) {
            $this->artisan('vendor:publish', ['--tag' => 'ui-assets', '--force' => true]);

            self::$published = true;
        }
    }
}
