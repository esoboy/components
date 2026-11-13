<div style="width: 480px; padding: 16px">
    <x-ui::button id="button">Save</x-ui::button>
    <x-ui::button id="outline" variant="outline">Outline</x-ui::button>
    <x-ui::button id="disabled" disabled>Disabled</x-ui::button>
    <x-ui::button id="custom" class="h-10 rounded-none">Custom</x-ui::button>
    <x-ui::button id="small" size="xs"><x-ui::icon id="icon" name="plus" /></x-ui::button>
    <x-ui::field>
        <x-ui::field.label for="input">Email</x-ui::field.label>
        <x-ui::input id="input" placeholder="Email" />
        <x-ui::input id="invalid" aria-invalid="true" />
        <x-ui::field.description>Help text</x-ui::field.description>
    </x-ui::field>
    <x-ui::textarea id="textarea" placeholder="Description" />
    <x-ui::checkbox id="checkbox" checked />
    <x-ui::switch id="switch" checked />
    <x-ui::native-select id="native-select"><x-ui::native-select.option value="a">Alpha</x-ui::native-select.option></x-ui::native-select>
    <x-ui::input-group>
        <x-ui::input-group.input id="group-input" />
        <x-ui::input-group.addon><x-ui::icon name="search" /></x-ui::input-group.addon>
    </x-ui::input-group>
</div>
