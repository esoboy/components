<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @vite('resources/css/app.css')
    @unless(request()->boolean('editors'))
        @uiScript
    @endunless
    @livewireStyles
</head>
<body>
    @if(request()->boolean('editors'))
        <x-ui::editor id="document-one" initial-value="One" />
        <x-ui::editor id="document-two" initial-value="Two" />
        <a href="/ui-package-test" wire:navigate>General components</a>
    @else
        <livewire:package-probe />
        <a href="/ui-package-test?editors=1" wire:navigate>Standalone editors</a>
    @endif
    @livewireScripts
</body>
</html>
