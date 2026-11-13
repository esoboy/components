<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo '<!--css-fixture-->';
echo Blade::render(file_get_contents(__DIR__.'/controls.blade.php'));
