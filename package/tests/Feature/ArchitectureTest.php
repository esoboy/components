<?php

it('uses Alpine lifecycles instead of a custom native JavaScript runtime', function () {
    $root = realpath(__DIR__.'/../../resources/js');
    expect(glob($root.'/components/*.js'))->toBeEmpty();
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'js') {
            continue;
        }
        $source = file_get_contents($file->getPathname());
        expect($source)->not->toMatch('/\bcustomElements\b|\bextends\s+HTMLElement\b|\bdispatchEvent\s*\(/');
        // SignaturePad exposes its own SDK event API; no DOM listener is registered here.
        $source = preg_replace('/this\._pad\.addEventListener\(/', 'sdkEvent(', $source);
        expect($source)->not->toMatch('/\b(?:addEventListener|removeEventListener)\s*\(|\.on(?:load|error|click|input|change|keydown|pointer\w+)\s*=/');
    }
});

it('keeps initialization and internal event wiring out of demo call sites', function () {
    foreach (glob(__DIR__.'/../../../application/resources/views/pages/*.blade.php') as $path) {
        $source = file_get_contents($path);
        expect($source)->not->toMatch('/\bx-(?:data|init|ref|on:)[\w:.-]*\s*=|<script\b/i');
    }
});

it('does not expose removed frontend default properties or obsolete parts', function () {
    foreach (componentViews() as $path) {
        expect(file_get_contents($path))->not->toMatch('/[\'"](?:default[A-Z]\w*|isActive|shouldFilter)[\'"]\s*=>/');
    }
    expect(view()->exists('ui::components.combobox.chips'))->toBeFalse()
        ->and(view()->exists('ui::components.questionnaire.item'))->toBeFalse()
        ->and(view()->exists('ui::components.tree.item'))->toBeFalse();
});
