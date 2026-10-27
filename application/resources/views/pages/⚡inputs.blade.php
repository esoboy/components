<?php

use Livewire\Component;

new class extends Component
{
    public array $region = ['zhejiang', 'hangzhou', 'xihu'];

    public array $category = [];

    public array $regions = [];

    public array $permissions = ['orders.view', 'users'];

    public ?string $file = 'button.blade.php';

    public ?string $department = null;

    public array $departments = [];

    public array $depts = [];

    public array $tags = ['laravel', 'livewire'];

    public ?int $quantity = 2;

    public ?float $price = 9.9;

    public function mount(): void
    {
        $this->regions = [
            ['value' => 'zhejiang', 'label' => '浙江', 'children' => [
                ['value' => 'hangzhou', 'label' => '杭州', 'children' => [
                    ['value' => 'xihu', 'label' => '西湖区'],
                    ['value' => 'binjiang', 'label' => '滨江区'],
                    ['value' => 'yuhang', 'label' => '余杭区'],
                ]],
                ['value' => 'ningbo', 'label' => '宁波', 'children' => [
                    ['value' => 'haishu', 'label' => '海曙区'],
                    ['value' => 'yinzhou', 'label' => '鄞州区'],
                ]],
            ]],
            ['value' => 'jiangsu', 'label' => '江苏', 'children' => [
                ['value' => 'nanjing', 'label' => '南京', 'children' => [
                    ['value' => 'xuanwu', 'label' => '玄武区'],
                    ['value' => 'gulou', 'label' => '鼓楼区', 'disabled' => true],
                ]],
            ]],
            ['value' => 'beijing', 'label' => '北京', 'disabled' => true],
        ];

        $this->depts = [
            ['value' => 'hq', 'label' => 'Headquarters', 'children' => [
                ['value' => 'eng', 'label' => 'Engineering', 'children' => [
                    ['value' => 'web', 'label' => 'Web'],
                    ['value' => 'mobile', 'label' => 'Mobile'],
                ]],
                ['value' => 'sales', 'label' => 'Sales'],
                ['value' => 'support', 'label' => 'Support', 'disabled' => true],
            ]],
        ];
    }
};
?>

<div class="mx-auto max-w-2xl space-y-10 p-10">
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Cascader</h2>
        <x-ui::field>
            <x-ui::field.label>Region</x-ui::field.label>
            <x-ui::cascader wire:model.live="region" :options="$regions" placeholder="Select a region" class="w-64" />
            <x-ui::field.description>Value: <span id="region">{{ json_encode($region, JSON_UNESCAPED_UNICODE) }}</span></x-ui::field.description>
        </x-ui::field>
        <x-ui::field>
            <x-ui::field.label>Any level</x-ui::field.label>
            <x-ui::cascader wire:model.live="category" :options="$regions" placeholder="Pick any level" change-on-select />
            <x-ui::field.description>Value: <span id="category">{{ json_encode($category, JSON_UNESCAPED_UNICODE) }}</span></x-ui::field.description>
        </x-ui::field>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tree</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="space-y-2">
                <x-ui::tree wire:model.live="permissions" checkable expand-all :items="[
                    ['value' => 'orders', 'label' => 'Orders', 'children' => [
                        ['value' => 'orders.view', 'label' => 'View orders'],
                        ['value' => 'orders.refund', 'label' => 'Refund orders'],
                        ['value' => 'orders.export', 'label' => 'Export', 'disabled' => true],
                    ]],
                    ['value' => 'users', 'label' => 'Users', 'children' => [
                        ['value' => 'users.view', 'label' => 'View users'],
                        ['value' => 'users.ban', 'label' => 'Ban users'],
                    ]],
                    ['value' => 'settings', 'label' => 'Settings'],
                ]" />
                <p class="text-xs text-muted-foreground">Checked: <span id="permissions">{{ implode(', ', $permissions) }}</span></p>
            </div>
            <div class="space-y-2">
                <x-ui::tree wire:model.live="file" :default-expanded="['components', 'ui']">
                    <x-ui::tree.item value="components" label="components">
                        <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg></x-slot:icon>
                        <x-ui::tree.item value="ui" label="ui">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg></x-slot:icon>
                            <x-ui::tree.item value="button.blade.php" label="button.blade.php" />
                            <x-ui::tree.item value="card.blade.php" label="card.blade.php" />
                        </x-ui::tree.item>
                        <x-ui::tree.item value="layout.blade.php" label="layout.blade.php" />
                    </x-ui::tree.item>
                    <x-ui::tree.item value="app.css" label="app.css" />
                </x-ui::tree>
                <p class="text-xs text-muted-foreground">Selected: <span id="file">{{ $file }}</span></p>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tree Select</h2>
        <div class="flex flex-wrap gap-4">
            <x-ui::tree-select wire:model.live="department" :items="$depts" placeholder="Department" expand-all class="w-56" />
            <x-ui::tree-select wire:model.live="departments" :items="$depts" checkable placeholder="Departments" expand-all class="w-72" />
        </div>
        <p class="text-xs text-muted-foreground">Single: <span id="department">{{ $department }}</span> · Multiple: <span id="departments">{{ implode(', ', $departments) }}</span></p>
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Tags Input &amp; Number Input</h2>
        <x-ui::field>
            <x-ui::field.label>Tags</x-ui::field.label>
            <x-ui::tags-input wire:model.live="tags" placeholder="Add a tag…" :max="5" />
            <x-ui::field.description>Tags: <span id="tags">{{ implode(', ', $tags) }}</span></x-ui::field.description>
        </x-ui::field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui::field>
                <x-ui::field.label>Quantity</x-ui::field.label>
                <x-ui::number-input wire:model.live="quantity" :min="1" :max="10" />
                <x-ui::field.description>Quantity: <span id="quantity">{{ $quantity }}</span></x-ui::field.description>
            </x-ui::field>
            <x-ui::field>
                <x-ui::field.label>Price</x-ui::field.label>
                <x-ui::number-input wire:model.live="price" :min="0" step="0.1" />
                <x-ui::field.description>Price: <span id="price">{{ $price }}</span></x-ui::field.description>
            </x-ui::field>
        </div>
    </section>
</div>
