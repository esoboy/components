<?php

// Install the tested ZIP into a disposable Laravel skeleton, as a Composer dist.
[$script, $host, $archive] = $argv;
$host = realpath($host);
$archive = realpath($archive);
$zip = new ZipArchive;
if (! $host || ! $archive || $zip->open($archive) !== true) {
    throw new RuntimeException('Expected a Laravel skeleton and a production ZIP.');
}
$package = json_decode($zip->getFromName('composer.json'), true, flags: JSON_THROW_ON_ERROR);
if ($zip->getFromName('LICENSE') !== file_get_contents(__DIR__.'/../../resources/distribution/LICENSE')) {
    throw new RuntimeException('The Composer archive must include the package license at its root.');
}
$zip->close();
$package['version'] = '1.0.5';
$checksum = sha1_file($archive);
$package['dist'] = [
    'type' => 'zip',
    'url' => 'file:///'.ltrim(str_replace('\\', '/', $archive), '/'),
    'reference' => $checksum,
    'shasum' => $checksum,
];
$manifestPath = $host.'/composer.json';
$manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
$manifest['repositories'] = [['type' => 'package', 'package' => $package]];
$manifest['require']['esoboy/ui'] = $package['version'];
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

$views = $host.'/resources/views';
copy(__DIR__.'/app.css', $host.'/resources/css/app.css');
if (! is_dir($views.'/components')) {
    mkdir($views.'/components', 0777, true);
}
copy(__DIR__.'/host.blade.php', $views.'/package-test.blade.php');
copy(__DIR__.'/probe.blade.php', $views.'/components/⚡package-probe.blade.php');
copy(__DIR__.'/state-probe.blade.php', $views.'/components/⚡state-probe.blade.php');
copy(__DIR__.'/composition-probe.blade.php', $views.'/components/⚡composition-probe.blade.php');
copy(__DIR__.'/interaction-probe.blade.php', $views.'/components/⚡interaction-probe.blade.php');
$route = "\\Illuminate\\Support\\Facades\\Route::view('/ui-package-test', 'package-test');";
if (! str_contains(file_get_contents($host.'/routes/web.php'), $route)) {
    file_put_contents($host.'/routes/web.php', "\n".$route."\n", FILE_APPEND);
}
