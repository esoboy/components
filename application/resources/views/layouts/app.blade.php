@php
$current = request()->path();
$page = collect(config('demo'))->collapse()->get($current);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $page ? $page[0].' · ' : '' }}esoboy/ui</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600" rel="stylesheet">

        @vite('resources/css/app.css')
    </head>
    <body class="min-h-screen antialiased">
        <x-ui::sidebar.provider>
            <x-ui::sidebar>
                <x-ui::sidebar.header>
                    <x-ui::sidebar.menu>
                        <x-ui::sidebar.menu-item>
                            <x-ui::sidebar.menu-button size="lg" href="/">
                                <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sm font-semibold text-sidebar-primary-foreground">ui</div>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-medium">esoboy/ui</span>
                                    <span class="truncate text-xs">Laravel 13 · Livewire 4</span>
                                </div>
                            </x-ui::sidebar.menu-button>
                        </x-ui::sidebar.menu-item>
                    </x-ui::sidebar.menu>
                </x-ui::sidebar.header>
                <x-ui::sidebar.content>
                    @foreach (config('demo') as $group => $pages)
                    <x-ui::sidebar.group>
                        <x-ui::sidebar.group-label>{{ $group }}</x-ui::sidebar.group-label>
                        <x-ui::sidebar.group-content>
                            <x-ui::sidebar.menu>
                                @foreach ($pages as $path => [$label, $components])
                                <x-ui::sidebar.menu-item>
                                    <x-ui::sidebar.menu-button :href="'/'.$path" :is-active="$current === $path">{{ $label }}</x-ui::sidebar.menu-button>
                                    <x-ui::sidebar.menu-badge>{{ count(explode(', ', $components)) }}</x-ui::sidebar.menu-badge>
                                </x-ui::sidebar.menu-item>
                                @endforeach
                            </x-ui::sidebar.menu>
                        </x-ui::sidebar.group-content>
                    </x-ui::sidebar.group>
                    @endforeach
                </x-ui::sidebar.content>
                <x-ui::sidebar.rail />
            </x-ui::sidebar>

            <x-ui::sidebar.inset>
                <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                    <x-ui::sidebar.trigger class="-ml-1" />
                    <x-ui::separator orientation="vertical" class="mr-2 h-4 self-center" />
                    <x-ui::breadcrumb>
                        <x-ui::breadcrumb.list>
                            @if ($page)
                            <x-ui::breadcrumb.item>
                                <x-ui::breadcrumb.link href="/">Components</x-ui::breadcrumb.link>
                            </x-ui::breadcrumb.item>
                            <x-ui::breadcrumb.separator />
                            <x-ui::breadcrumb.item>
                                <x-ui::breadcrumb.page>{{ $page[0] }}</x-ui::breadcrumb.page>
                            </x-ui::breadcrumb.item>
                            @else
                            <x-ui::breadcrumb.item>
                                <x-ui::breadcrumb.page>Components</x-ui::breadcrumb.page>
                            </x-ui::breadcrumb.item>
                            @endif
                        </x-ui::breadcrumb.list>
                    </x-ui::breadcrumb>
                </header>

                {{ $slot }}
            </x-ui::sidebar.inset>
        </x-ui::sidebar.provider>

        <x-ui::toaster />
        <x-ui::confirm />

        @uiScripts
    </body>
</html>
