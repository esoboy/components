<eso-date-picker {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'style', 'name'])->merge(['data-slot' => 'date-picker', 'data-mode' => $settings['mode'], 'data-time' => (bool) ($settings['time'] ?? false), ...($settings['validation'] ?? [])]) }}>
    <x-ui::popover>
        <x-ui::popover.trigger variant="outline" :disabled="$settings['disabled'] ?? false" data-date-picker-trigger :data-empty="$label === '' ? 'true' : 'false'" :attributes="$attributes->only(['class', 'style'])->merge($settings['validation'] ?? [])">
            @if ($settings['mode'] === 'range')<x-ui::icon name="calendar" data-icon="inline-start" />@endif
            <span data-slot="date-picker-value">{{ $label ?: ($settings['placeholder'] ?? null) }}</span>
            @if ($settings['mode'] !== 'range')<x-ui::icon name="chevron-down" data-icon="inline-end" />@endif
        </x-ui::popover.trigger>
        <x-ui::popover.content align="start" data-date-picker-content>
            <eso-calendar data-slot="calendar" data-mode="{{ $settings['mode'] }}" data-month="{{ $month }}" data-week-starts-on="{{ $settings['weekStartsOn'] ?? ((int) \IntlCalendar::createInstance(null, app()->getLocale())->getFirstDayOfWeek() - 1) }}" x-data="uiCalendar" x-on:keydown="key($event)">
                @include('ui::livewire.calendar-grid')
            </eso-calendar>
            @if (($settings['time'] ?? false))
                <div data-slot="date-picker-time">
                    <x-ui::field.group>
                        @foreach ($settings['mode'] === 'range' ? ['from' => __('ui::date-picker.start-time'), 'to' => __('ui::date-picker.end-time')] : ['from' => __('ui::date-picker.time')] as $part => $timeLabel)
                            <x-ui::field>
                                <x-ui::field.label :for="$this->getId().'-'.$part">{{ $timeLabel }}</x-ui::field.label>
                                <x-ui::input-group>
                                    <x-ui::input-group.input :id="$this->getId().'-'.$part" type="time" :step="($settings['step'] ?? null)" :value="$times[$part]" :data-date-picker-time="$part" wire:change="setTime('{{ $part }}', $event.target.value)" />
                                    <x-ui::input-group.addon><x-ui::icon name="clock-2" /></x-ui::input-group.addon>
                                </x-ui::input-group>
                            </x-ui::field>
                        @endforeach
                    </x-ui::field.group>
                </div>
            @endif
            @if (($settings['presets'] ?? []))
                <div data-slot="date-picker-presets">
                    @foreach (($settings['presets'] ?? []) as $presetLabel => $presetValue)
                        <x-ui::button variant="outline" size="sm" :data-date-picker-preset="json_encode($presetValue)" wire:click="preset({{ \Illuminate\Support\Js::from($presetLabel) }})">{{ $presetLabel }}</x-ui::button>
                    @endforeach
                </div>
            @endif
        </x-ui::popover.content>
    </x-ui::popover>
    @include('ui::livewire.form-value')
</eso-date-picker>
