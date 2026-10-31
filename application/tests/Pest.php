<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Browser tests drive the demo pages in Chromium: what jsdom can't show (the Popover API,
 * <dialog>, anchor positioning, Livewire round trips and morphs) is tested here.
 */

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Browser');

pest()->browser()->timeout(10_000);

/**
 * A script telling whether the element is open: a modal dialog or a shown popover.
 */
function opened(string $selector): string
{
    return 'document.querySelector('.json_encode($selector).")?.matches(':modal, :popover-open') === true";
}
