<?php

use Esoboy\Ui\Support\Icons;
use Illuminate\Support\Facades\Blade;

it('renders official Lucide geometry through the public Blade component', function () {
    $html = Blade::render('<x-ui::icon name="circle-question-mark" size="lg" />');
    expect($html)->toContain('width="20"', 'viewBox="0 0 24 24"', Icons::content('circle-question-mark'))
        ->and(Icons::content('loader-circle'))->toContain('<path');
});

it('rejects names absent from the official catalog', function (string $name) {
    expect(fn () => Icons::content($name))->toThrow(InvalidArgumentException::class);
})->with(['circle-help', 'help-circle', 'loader-2', 'alert-circle', 'no-such-ui-icon']);
