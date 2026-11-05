<eso-calendar {{ $attributes->whereDoesntStartWith('wire:model')->except('name')->merge(['data-slot' => 'calendar', 'data-mode' => $settings['mode'] ?? 'single', 'data-week-starts-on' => $settings['weekStartsOn'] ?? ((int) \IntlCalendar::createInstance(null, app()->getLocale())->getFirstDayOfWeek() - 1), 'data-value' => json_encode($value), 'data-month' => $month, 'x-data' => 'uiCalendar', 'x-on:keydown' => 'key($event)']) }}>
    @include('ui::livewire.calendar-grid')
    @include('ui::livewire.form-value')
</eso-calendar>
