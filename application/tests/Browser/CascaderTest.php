<?php

/*
 * Cascader on the Inputs page: a column per level in a popover; the value is the path.
 */

const CASCADER = 'eso-cascader[wire\\:model\\.live="region"]';

it('shows the bound path on its trigger', function () {
    visit('/inputs')
        ->assertSeeIn(CASCADER.' [data-cascader-trigger]', '浙江 / 杭州 / 西湖区')
        ->assertNoJavaScriptErrors();
});

it('picks a path column by column and closes', function () {
    visit('/inputs')
        ->click(CASCADER.' [data-cascader-trigger]')
        ->assertScript(opened(CASCADER.' [data-slot="cascader-content"]'))
        ->click(CASCADER.' [data-cascader-item][data-value="jiangsu"]')
        ->click(CASCADER.' [data-cascader-item][data-value="nanjing"]')
        ->click(CASCADER.' [data-cascader-item][data-value="xuanwu"]')
        ->assertScript(opened(CASCADER.' [data-slot="cascader-content"]'), false)
        ->assertSeeIn('#region', '["jiangsu","nanjing","xuanwu"]')
        ->assertSeeIn(CASCADER.' [data-cascader-trigger]', '江苏 / 南京 / 玄武区');
});

it('shows disabled options it does not pick', function () {
    visit('/inputs')
        ->click(CASCADER.' [data-cascader-trigger]')
        ->assertAttribute(CASCADER.' [data-cascader-item][data-value="beijing"]', 'aria-disabled', 'true');
});

it('picks any level with change-on-select', function () {
    visit('/inputs')
        ->click('eso-cascader[wire\\:model\\.live="category"] [data-cascader-trigger]')
        ->click('eso-cascader[wire\\:model\\.live="category"] [data-cascader-item][data-value="zhejiang"]')
        ->assertSeeIn('#category', '["zhejiang"]');
});
