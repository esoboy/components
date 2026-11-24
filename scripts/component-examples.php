<?php

$resources = dirname(__DIR__).'/resources';
$catalog = file_get_contents($resources.'/boost/skills/esoboy-ui-development/references/components.md');
preg_match_all('/^## ([\w-]+)\h*\n\s*```blade\h*\n(.+?)\n```\h*$/ms', str_replace("\r\n", "\n", $catalog), $matches, PREG_SET_ORDER);

$public = [];
foreach (new FilesystemIterator($resources.'/views/components') as $entry) {
    if ($entry->isFile() && str_ends_with($entry->getFilename(), '.blade.php')) {
        $public[] = $entry->getBasename('.blade.php');
    } elseif ($entry->isDir() && is_file($entry->getPathname().'/index.blade.php')) {
        $public[] = $entry->getFilename();
    }
}

$examples = [];
foreach ($matches as [, $name, $blade]) {
    if (isset($examples[$name])) {
        throw new RuntimeException("Duplicate Boost component example [$name].");
    }
    $examples[$name] = $blade;
}

$missing = array_diff($public, array_keys($examples));
$unknown = array_diff(array_keys($examples), $public);
if ($missing || $unknown) {
    throw new RuntimeException(sprintf('Boost examples missing for [%s]. Unknown components [%s].', implode(', ', $missing), implode(', ', $unknown)));
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    printf("Verified %d public component examples.\n", count($public));
}

return $examples;
