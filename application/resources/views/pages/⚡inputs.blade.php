<?php

use Livewire\Component;

new class extends Component
{
    public array $region = ['zhejiang', 'hangzhou', 'xihu'];

    public array $category = [];

    public array $regions = [];

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
</div>
