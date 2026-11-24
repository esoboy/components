<?php

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;

it('only adds a color class for an explicit supported status', function (string $tag, string $props, string $expectedTag) {
    $render = static function (string $attributes) use ($tag, $props) {
        $html = Blade::render("<x-ui::{$tag} {$props} {$attributes} />");

        return HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('body > *');
    };

    $base = $render('');
    expect($base->localName)->toBe($expectedTag);
    foreach ([null, '', 'unrecognized'] as $empty) {
        $prop = $empty === null ? ':status="null"' : 'status="'.$empty.'"';
        expect(trim($render($prop)->className))->toBe(trim($base->className));
    }
    foreach (['neutral', 'info', 'success', 'warning', 'error'] as $status) {
        $root = $render('status="'.$status.'"');
        expect($root->localName)->toBe($base->localName)
            ->and($root->getAttribute('role'))->toBe($base->getAttribute('role'))
            ->and($root->hasAttribute('status'))->toBeFalse()
            ->and($root->classList->contains('ui-status-'.$status))->toBeTrue();
        $root->classList->remove('ui-status-'.$status);
        expect(trim($root->className))->toBe(trim($base->className));
    }
})->with([
    ['button', 'variant="outline"', 'button'],
    ['button', 'variant="link" link="/details"', 'a'],
    ['button.text', 'variant="secondary"', 'span'],
    ['badge', 'variant="outline"', 'span'],
    ['badge', 'variant="secondary" link="/details"', 'a'],
    ['switch', 'checked', 'span'],
    ['alert', 'variant="destructive"', 'div'],
    ['toast', 'variant="destructive"', 'div'],
    ['text', '', 'p'],
    ['text', 'link="/details"', 'a'],
    ['icon', 'name="check"', 'svg'],
    ['statistic', 'title="Orders" value="5"', 'div'],
    ['progress', ':value="60"', 'div'],
    ['timeline.item', '', 'li'],
    ['avatar.badge', '', 'span'],
    ['alert-dialog.content', '', 'dialog'],
]);
