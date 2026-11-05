<?php

namespace Esoboy\Ui\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use IntlDateFormatter;

class DatePicker extends Calendar
{
    public function label(): string
    {
        $mode = $this->settings['mode'] ?? 'single';
        $format = function (?string $value) use ($mode): string {
            if (! $value) {
                return '';
            }
            if (! ($this->settings['time'] ?? false)) {
                return $this->format($value, $mode === 'single' ? 'yMMMMd' : 'yMMMdd');
            }
            $formatter = new IntlDateFormatter(app()->getLocale(), $mode === 'single' ? IntlDateFormatter::LONG : IntlDateFormatter::MEDIUM, ! empty($this->settings['step']) && $this->settings['step'] < 60 ? IntlDateFormatter::MEDIUM : IntlDateFormatter::SHORT, config('app.timezone'));

            return str_replace("\u{202F}", ' ', $formatter->format(CarbonImmutable::parse($value)) ?: $value);
        };

        return is_array($this->value)
            ? implode($mode === 'range' ? ' - ' : ', ', array_map($format, array_values(array_filter($this->value))))
            : $format($this->value);
    }

    public function render(): View
    {
        return view('ui::livewire.date-picker', ['months' => $this->months(), 'label' => $this->label()]);
    }
}
