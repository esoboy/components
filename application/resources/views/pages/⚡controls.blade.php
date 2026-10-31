<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Esoboy\Ui\Facades\Ui;
use Livewire\Component;

new class extends Component
{
    public bool $bold = false;

    public ?string $align = 'left';

    public array $styles = ['bold'];

    public int $volume = 50;

    public array $price = [25, 75];

    #[Validate('required|digits:6')]
    public string $code = '';

    #[Validate('required|in:apple,banana,blueberry')]
    public ?string $fruit = null;

    public string $timezone = 'cst';

    public ?string $framework = null;

    public array $frameworks = ['next'];

    public string $ran = '';

    public ?string $date = '2026-10-26';

    public array $range = ['from' => '2026-10-20', 'to' => '2026-11-03'];

    public array $period = ['from' => null, 'to' => null];

    public ?string $time = '10:30:00';

    public ?string $startsAt = '2026-10-29 10:30';

    public array $shift = ['from' => null, 'to' => null];

    public string $email = '';

    public string $city = '';

    #[Validate('required|date')]
    public ?string $birthday = null;

    /**
     * The typed name at each mail domain that starts with what follows its @.
     *
     * @return list<string>
     */
    #[Computed]
    public function emailSuggestions(): array
    {
        [$name, $domain] = array_pad(explode('@', $this->email, 2), 2, null);

        return $name === '' ? [] : collect(['gmail.com', 'outlook.com', 'qq.com', '163.com'])
            ->filter(fn (string $host) => $domain === null || str_starts_with($host, $domain))
            ->map(fn (string $host) => "{$name}@{$host}")
            ->values()
            ->all();
    }

    public function saveBirthday(): void
    {
        $this->validate(['birthday' => 'required|date']);
    }

    public function run(string $command): void
    {
        $this->ran = $command;
        Ui::dialog('command-menu')->close();
    }

    public function verify(): void
    {
        $this->validate(['code' => 'required|digits:6']);
    }

    public function pick(): void
    {
        $this->validate(['fruit' => 'required|in:apple,banana,blueberry']);
    }
};
?>

<div class="mx-auto max-w-3xl space-y-12 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Calendar</h2>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::calendar id="demo-calendar" wire:model.live="date" caption-layout="dropdown" class="rounded-lg border" />
            <x-ui::calendar id="demo-range" mode="range" wire:model.live="range" :number-of-months="2" class="rounded-lg border" />
        </div>
        <p class="text-sm text-muted-foreground">date: <span id="date">{{ $date ?? 'none' }}</span>, range: <span id="range">{{ ($range['from'] ?? '').' → '.($range['to'] ?? '') }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Date Picker</h2>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::date-picker id="demo-picker" wire:model.live="date" placeholder="Pick a date" />
            <x-ui::date-picker mode="range" wire:model.live="range" placeholder="Pick a date range" />
            <x-ui::date-picker id="demo-presets" mode="range" wire:model.live="period" placeholder="Pick a period" :presets="[
                'Today' => [today(), today()],
                'Last 7 days' => [today()->subDays(6), today()],
                'Last 30 days' => [today()->subDays(29), today()],
                'This month' => [today()->startOfMonth(), today()->endOfMonth()],
            ]" />
        </div>
        <p class="text-sm text-muted-foreground">period: <span id="period">{{ ($period['from'] ?? '').' → '.($period['to'] ?? '') }}</span></p>
        <div class="flex items-start gap-4">
            <x-ui::field class="w-fit">
                <x-ui::label for="demo-date-time">Date</x-ui::label>
                <x-ui::date-picker id="demo-date-time" wire:model.live="date" placeholder="Select date" class="w-44" />
            </x-ui::field>
            <x-ui::field class="w-32">
                <x-ui::label for="demo-time">Time</x-ui::label>
                <x-ui::time-picker id="demo-time" step="1" wire:model.live="time" />
            </x-ui::field>
        </div>
        <p class="text-sm text-muted-foreground">time: <span id="time">{{ $time ?? 'none' }}</span></p>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::date-picker id="demo-datetime" time wire:model.live="startsAt" placeholder="Pick a date and time" />
            <x-ui::date-picker id="demo-shift" mode="range" time default-time="09:00" wire:model.live="shift" placeholder="Pick a shift" />
        </div>
        <p class="text-sm text-muted-foreground">startsAt: <span id="starts-at">{{ $startsAt ?? 'none' }}</span>, shift: <span id="shift">{{ ($shift['from'] ?? '').' → '.($shift['to'] ?? '') }}</span></p>
        <form wire:submit="saveBirthday" class="flex items-start gap-3">
            <x-ui::field class="w-fit">
                <x-ui::label for="birthday">Date of birth</x-ui::label>
                <x-ui::date-picker id="birthday" wire:model="birthday" placeholder="Select date" caption-layout="dropdown" />
            </x-ui::field>
            <x-ui::button type="submit" class="mt-6">Save</x-ui::button>
        </form>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Select</h2>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::select id="demo-select" wire:model.live="timezone">
                <x-ui::select.trigger class="w-full max-w-48">
                    <x-ui::select.value placeholder="Select a timezone" />
                </x-ui::select.trigger>
                <x-ui::select.content>
                    <x-ui::select.group>
                        <x-ui::select.label>North America</x-ui::select.label>
                        <x-ui::select.item value="est">Eastern Standard Time</x-ui::select.item>
                        <x-ui::select.item value="cst">Central Standard Time</x-ui::select.item>
                        <x-ui::select.item value="mst">Mountain Standard Time</x-ui::select.item>
                        <x-ui::select.item value="pst" disabled>Pacific Standard Time</x-ui::select.item>
                    </x-ui::select.group>
                    <x-ui::select.separator />
                    <x-ui::select.group>
                        <x-ui::select.label>Europe &amp; Africa</x-ui::select.label>
                        <x-ui::select.item value="gmt">Greenwich Mean Time</x-ui::select.item>
                        <x-ui::select.item value="cet">Central European Time</x-ui::select.item>
                    </x-ui::select.group>
                </x-ui::select.content>
            </x-ui::select>
            <span class="text-sm text-muted-foreground">timezone: <span id="timezone">{{ $timezone }}</span></span>
        </div>
        <form wire:submit="pick" class="flex items-start gap-3">
            <x-ui::field class="w-48">
                <x-ui::select id="fruit" wire:model="fruit">
                    <x-ui::select.trigger class="w-full">
                        <x-ui::select.value placeholder="Select a fruit" />
                    </x-ui::select.trigger>
                    <x-ui::select.content>
                        <x-ui::select.group>
                            <x-ui::select.label>Fruits</x-ui::select.label>
                            <x-ui::select.item value="apple">Apple</x-ui::select.item>
                            <x-ui::select.item value="banana">Banana</x-ui::select.item>
                            <x-ui::select.item value="blueberry">Blueberry</x-ui::select.item>
                            <x-ui::select.item value="grapes">Grapes (fails)</x-ui::select.item>
                        </x-ui::select.group>
                    </x-ui::select.content>
                </x-ui::select>
            </x-ui::field>
            <x-ui::button type="submit">Pick</x-ui::button>
        </form>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Combobox</h2>
        @php $options = ['next' => 'Next.js', 'sveltekit' => 'SvelteKit', 'nuxt' => 'Nuxt.js', 'remix' => 'Remix', 'astro' => 'Astro']; @endphp
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::combobox id="demo-combobox" wire:model.live="framework">
                <x-ui::combobox.input placeholder="Select a framework" show-clear />
                <x-ui::combobox.content>
                    <x-ui::combobox.empty>No items found.</x-ui::combobox.empty>
                    <x-ui::combobox.list>
                        @foreach ($options as $value => $label)
                            <x-ui::combobox.item :value="$value">{{ $label }}</x-ui::combobox.item>
                        @endforeach
                    </x-ui::combobox.list>
                </x-ui::combobox.content>
            </x-ui::combobox>
            <x-ui::combobox id="demo-chips" multiple wire:model.live="frameworks">
                <x-ui::combobox.chips class="w-full max-w-xs">
                    <x-ui::combobox.chips-input placeholder="Add framework" />
                </x-ui::combobox.chips>
                <x-ui::combobox.content>
                    <x-ui::combobox.empty>No items found.</x-ui::combobox.empty>
                    <x-ui::combobox.list>
                        @foreach ($options as $value => $label)
                            <x-ui::combobox.item :value="$value">{{ $label }}</x-ui::combobox.item>
                        @endforeach
                    </x-ui::combobox.list>
                </x-ui::combobox.content>
            </x-ui::combobox>
        </div>
        <p class="text-sm text-muted-foreground">framework: <span id="framework">{{ $framework ?? 'none' }}</span>, frameworks: <span id="frameworks">{{ implode(',', $frameworks) }}</span></p>
        <p class="text-sm text-muted-foreground">With autocomplete the value is the typed text: suggestions from the server as you type, or filtered here.</p>
        <div class="flex flex-wrap items-start gap-6">
            <x-ui::combobox id="demo-email" autocomplete :should-filter="false" wire:model.live.debounce.150ms="email">
                <x-ui::combobox.input placeholder="Email" :show-trigger="false" class="w-64" />
                <x-ui::combobox.content>
                    <x-ui::combobox.list>
                        @foreach ($this->emailSuggestions as $suggestion)
                            <x-ui::combobox.item :value="$suggestion" wire:key="email-{{ $suggestion }}">{{ $suggestion }}</x-ui::combobox.item>
                        @endforeach
                    </x-ui::combobox.list>
                </x-ui::combobox.content>
            </x-ui::combobox>
            <x-ui::combobox id="demo-city" autocomplete wire:model.live="city">
                <x-ui::combobox.input placeholder="City" :show-trigger="false" show-clear class="w-56" />
                <x-ui::combobox.content>
                    <x-ui::combobox.list>
                        @foreach (['Hangzhou', 'Shanghai', 'Shenzhen', 'Suzhou', 'Beijing', 'Chengdu'] as $name)
                            <x-ui::combobox.item :value="$name">{{ $name }}</x-ui::combobox.item>
                        @endforeach
                    </x-ui::combobox.list>
                </x-ui::combobox.content>
            </x-ui::combobox>
        </div>
        <p class="text-sm text-muted-foreground">email: <span id="email">{{ $email ?: 'none' }}</span>, city: <span id="city">{{ $city ?: 'none' }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Command</h2>
        <x-ui::command id="demo-command" class="max-w-sm rounded-lg border">
            <x-ui::command.input placeholder="Type a command or search..." />
            <x-ui::command.list>
                <x-ui::command.empty>No results found.</x-ui::command.empty>
                <x-ui::command.group heading="Suggestions">
                    <x-ui::command.item wire:click="run('calendar')">Calendar</x-ui::command.item>
                    <x-ui::command.item wire:click="run('emoji')">Search Emoji</x-ui::command.item>
                    <x-ui::command.item disabled>Calculator</x-ui::command.item>
                </x-ui::command.group>
                <x-ui::command.separator />
                <x-ui::command.group heading="Settings">
                    <x-ui::command.item wire:click="run('profile')">Profile <x-ui::command.shortcut>⌘P</x-ui::command.shortcut></x-ui::command.item>
                    <x-ui::command.item wire:click="run('billing')">Billing <x-ui::command.shortcut>⌘B</x-ui::command.shortcut></x-ui::command.item>
                    <x-ui::command.item wire:click="run('settings')" :keywords="['preferences']">Settings <x-ui::command.shortcut>⌘S</x-ui::command.shortcut></x-ui::command.item>
                </x-ui::command.group>
            </x-ui::command.list>
        </x-ui::command>
        <div class="flex items-center gap-3">
            <x-ui::button variant="outline" commandfor="command-menu" command="show-modal">Open command menu</x-ui::button>
            <span class="text-sm text-muted-foreground">ran: <span id="ran">{{ $ran ?: 'nothing' }}</span></span>
        </div>
        <x-ui::command.dialog name="command-menu">
            <x-ui::command.input placeholder="Type a command or search..." />
            <x-ui::command.list>
                <x-ui::command.empty>No results found.</x-ui::command.empty>
                <x-ui::command.group heading="Suggestions">
                    <x-ui::command.item wire:click="run('calendar')">Calendar</x-ui::command.item>
                    <x-ui::command.item wire:click="run('emoji')">Search Emoji</x-ui::command.item>
                </x-ui::command.group>
            </x-ui::command.list>
        </x-ui::command.dialog>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Toggle</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui::toggle aria-label="Toggle bold" wire:model.live="bold"><x-ui::icon name="bold" /></x-ui::toggle>
            <x-ui::toggle variant="outline" aria-label="Toggle italic" default-pressed><x-ui::icon name="italic" /> Italic</x-ui::toggle>
            <x-ui::toggle size="sm" aria-label="Toggle underline" disabled><x-ui::icon name="underline" /></x-ui::toggle>
            <span class="text-sm text-muted-foreground">bold: <span id="bold">{{ $bold ? 'on' : 'off' }}</span></span>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Toggle Group</h2>
        <div class="flex flex-wrap items-center gap-6">
            <x-ui::toggle-group variant="outline" wire:model.live="align">
                <x-ui::toggle-group.item value="left">Left</x-ui::toggle-group.item>
                <x-ui::toggle-group.item value="center">Center</x-ui::toggle-group.item>
                <x-ui::toggle-group.item value="right">Right</x-ui::toggle-group.item>
            </x-ui::toggle-group>
            <x-ui::toggle-group type="multiple" variant="outline" spacing="0" size="sm" wire:model.live="styles">
                <x-ui::toggle-group.item value="bold" aria-label="Bold"><x-ui::icon name="bold" /></x-ui::toggle-group.item>
                <x-ui::toggle-group.item value="italic" aria-label="Italic"><x-ui::icon name="italic" /></x-ui::toggle-group.item>
                <x-ui::toggle-group.item value="underline" aria-label="Underline"><x-ui::icon name="underline" /></x-ui::toggle-group.item>
            </x-ui::toggle-group>
        </div>
        <p class="text-sm text-muted-foreground">align: <span id="align">{{ $align ?? 'none' }}</span>, styles: <span id="styles">{{ implode(',', $styles) }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Slider</h2>
        <div class="grid max-w-xs gap-6">
            <x-ui::slider wire:model.live="volume" :max="100" :step="1" aria-label="Volume" />
            <x-ui::slider wire:model.live="price" :max="100" :step="5" aria-label="Price" />
            <x-ui::slider :default-value="[33]" aria-label="Disabled" disabled />
        </div>
        <p class="text-sm text-muted-foreground">volume: <span id="volume">{{ $volume }}</span>, price: <span id="price">{{ implode('-', $price) }}</span></p>
        <div class="flex h-40 gap-6">
            <x-ui::slider :default-value="[50]" orientation="vertical" aria-label="Vertical" />
            <x-ui::slider :default-value="[25, 75]" orientation="vertical" aria-label="Vertical range" />
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Input OTP</h2>
        <form wire:submit="verify" class="space-y-3">
            <x-ui::field>
                <x-ui::label for="code">Verification code</x-ui::label>
                <x-ui::input-otp id="code" maxlength="6" inputmode="numeric" wire:model="code">
                    <x-ui::input-otp.group>
                        <x-ui::input-otp.slot :index="0" />
                        <x-ui::input-otp.slot :index="1" />
                        <x-ui::input-otp.slot :index="2" />
                    </x-ui::input-otp.group>
                    <x-ui::input-otp.separator />
                    <x-ui::input-otp.group>
                        <x-ui::input-otp.slot :index="3" />
                        <x-ui::input-otp.slot :index="4" />
                        <x-ui::input-otp.slot :index="5" />
                    </x-ui::input-otp.group>
                </x-ui::input-otp>
            </x-ui::field>
            <x-ui::button type="submit">Verify</x-ui::button>
        </form>
    </section>
</div>
