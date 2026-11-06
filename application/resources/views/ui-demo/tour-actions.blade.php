@if ($step > 0)
    <x-ui::tour.action action="previous">
        <x-ui::button variant="outline" size="sm">Back</x-ui::button>
    </x-ui::tour.action>
@endif
@if ($step + 1 < $total)
    <x-ui::tour.action action="next">
        <x-ui::button size="sm">Continue</x-ui::button>
    </x-ui::tour.action>
@else
    <x-ui::tour.action action="finish">
        <x-ui::button size="sm">Finish</x-ui::button>
    </x-ui::tour.action>
@endif
