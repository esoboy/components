# esoboy/ui

shadcn/ui for Laravel 13 and Livewire 4: the same components, markup and look, as Blade
components that bind with `wire:model`.

## Requirements

- PHP 8.3+, Laravel 13, Livewire 4
- Tailwind CSS 4

## Installation

```bash
composer require esoboy/ui
```

Import the styles after Tailwind in the app's CSS entry:

```css
@import 'tailwindcss';
@import '../../vendor/esoboy/ui/resources/css/ui.css';
```

Load the scripts and place the toaster and the confirm dialog once in the layout:

```blade
<body>
    {{ $slot }}

    <x-ui::toaster />
    <x-ui::confirm />

    @uiScripts
</body>
```

`@uiScripts` loads one deferred script with every component. Heavy components (Code Block
highlighting, …) load their own script the first time they appear on a page, also when
they arrive in a Livewire update; the app installs and imports nothing else.

The package serves its scripts from `/esoboy-ui/…`. In production, publish them so the web
server serves them without booting Laravel:

```bash
php artisan vendor:publish --tag=ui-assets --force
```

Apps created from the Laravel skeleton already republish the `laravel-assets` group on
`composer update`, which includes these files. Published files are used only while they
match the installed version; otherwise the route serves them.

## Usage

Components are `<x-ui::name>`, their parts `<x-ui::name.part>`, named and shaped like
shadcn/ui's:

```blade
<x-ui::field>
    <x-ui::field.label for="email">Email</x-ui::field.label>
    <x-ui::input id="email" type="email" wire:model.live.blur="email" />
</x-ui::field>
```

Controls inside `<x-ui::field>` show their validation errors; standalone controls don't.

From Livewire components, form objects and actions:

```php
use Esoboy\Ui\Facades\Ui;

Ui::toast('Saved', type: 'success');
Ui::dialog('edit-profile')->close();
Ui::countdown('sms');
```

## Customizing

- **Theme:** every token lives in `@theme`; a later `@theme` block in the app overrides it.
  Light and dark values follow the system through `color-scheme`.
- **Styles:** component defaults sit in `@layer components`, so any utility class on a
  component wins without `!`.
- **Strings:** override single lines in `lang/vendor/ui/{locale}/{component}.php`, or
  publish them all with `php artisan vendor:publish --tag=ui-lang`.

## Development

```bash
composer test       # Pest
composer lint       # Pint
composer analyse    # Larastan
npm test            # Vitest
npm run build       # dist/ (npm run dev rebuilds on change)
```

`dist/` is a build output and isn't committed on branches; a release builds it into its tag.
