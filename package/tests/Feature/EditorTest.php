<?php

use Dom\Element;
use Dom\HTMLDocument;
use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Support\RichText;
use Esoboy\Ui\Tests\Fixtures\Posts;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\ViewException;
use Livewire\Livewire;

/*
 * Editor: Tiptap in the editor.js module, writing what its format allows, with HTML
 * sanitized to that format on the way in (the view) and back (#[Sanitize], Ui::sanitize()).
 */

// The controls a toolbar shows (not those in its menus and popovers), by what names them.
function controls(string $html): array
{
    $document = HTMLDocument::createFromString("<!DOCTYPE html><body>{$html}</body>", LIBXML_NOERROR);
    $controls = $document->querySelectorAll('[data-slot="editor-toolbar"] :is([data-editor-command], [data-editor-action], [data-editor-opens], [data-editor-menu])');

    return array_values(array_map(
        fn (Element $control) => $control->getAttribute('data-editor-menu') ?? $control->getAttribute('data-editor-opens') ?? $control->getAttribute('data-editor-command') ?? $control->getAttribute('data-editor-action'),
        array_filter(iterator_to_array($controls), fn (Element $control) => $control->closest('[popover]') === null),
    ));
}

describe('formats', function () {
    it('resolves presets, lists of features and presets, and named formats', function () {
        RichText::format('comment', ['bold', 'italic', 'link', 'emoji']);

        expect(RichText::resolve('text')->features)->toBe([])
            ->and(RichText::resolve('basic')->has('color', 'image'))->toBeTrue()
            ->and(RichText::resolve('basic')->has('image'))->toBeFalse()
            ->and(RichText::resolve()->features)->toBe(RichText::FEATURES)
            ->and(RichText::resolve(['link', 'bold', 'link'])->features)->toBe(['bold', 'link'])
            ->and(RichText::resolve(['basic', 'table'])->features)->toBe([...RichText::PRESETS['basic'], 'table'])
            ->and(RichText::resolve('comment')->features)->toBe(['bold', 'italic', 'link', 'emoji'])
            ->and(RichText::resolve(['comment', 'image'])->has('image', 'bold'))->toBeTrue();
    });

    it('refuses what it does not know', function (string|array $format, string $message) {
        expect(fn () => RichText::resolve($format))->toThrow(InvalidArgumentException::class, $message);
    })->with([
        'a format' => ['short', '[short] is not a rich text format'],
        'a feature' => [['bold', 'blink'], '[blink] is not a rich text feature'],
    ]);

    it('keeps preset names for presets', function () {
        Ui::editorFormat('basic', ['bold']);
    })->throws(InvalidArgumentException::class, '[basic] is a preset');

    it('offers every tool but typography, and plain text only the plain ones', function () {
        expect(RichText::resolve('full')->tools())->toBe(['toolbar', 'bubble-menu', 'slash-menu', 'drag-handle', 'find-replace', 'source', 'fullscreen', 'character-count', 'markdown'])
            ->and(RichText::resolve('text')->tools())->toBe(['toolbar', 'find-replace', 'fullscreen', 'character-count'])
            ->and(RichText::resolve('text')->tools(['typography']))->toBe(['typography'])
            ->and(fn () => RichText::resolve()->tools(['spellcheck']))->toThrow(InvalidArgumentException::class, '[spellcheck] is not an Editor tool');
    });
});

describe('the view', function () {
    it('renders the editor with its format, tools and sanitized content', function () {
        $html = (string) $this->blade('<x-ui::editor wire:model="body" placeholder="Write" :max-length="500" default-value="<p onclick=\'x\'>Hi <b>there</b></p><script>alert(1)</script>" />');

        expect($html)
            ->toContain('<eso-editor data-slot="editor" data-format="{&quot;features&quot;:[&quot;heading&quot;,')
            ->toContain('data-tools="toolbar bubble-menu slash-menu drag-handle find-replace source fullscreen character-count markdown" data-labels="{&quot;expand&quot;:&quot;Expand&quot;,&quot;collapse&quot;:&quot;Collapse&quot;,&quot;task&quot;:&quot;Done&quot;}" data-max-length="500" data-emoji="http://localhost/esoboy-ui/emoji/en.json?id=')
            ->toContain('data-placeholder="Write" data-label="Rich text editor" wire:model="body">')
            ->toContain('<div data-slot="editor-toolbar" role="toolbar" aria-label="Formatting" wire:ignore>')
            ->toContain('data-editor-command="bold" aria-label="Bold" aria-pressed="false" data-editor-keys="Mod-b"><svg')
            ->toContain('<div data-slot="editor-find" role="search" aria-label="Find and replace" hidden wire:ignore>')
            ->toContain('<div data-slot="editor-content" wire:ignore><p>Hi <b>there</b></p></div>')
            ->toContain('<textarea data-slot="editor-source" aria-label="HTML source" spellcheck="false" hidden wire:ignore></textarea>')
            ->toContain('<div data-slot="editor-footer" wire:ignore><span data-editor-count></span> characters</div>')
            ->toContain('data-slot="tooltip-content" data-side="top" data-align="center" data-editor-tip')
            ->not->toContain('onclick')
            ->not->toContain('alert')
            ->and(controls($html))->toBe(['undo', 'redo', 'heading', 'font-family', 'font-size', 'bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'color', 'clear-formatting', 'link', 'text-align', 'line-height', 'outdent', 'indent', 'text-indent', 'bullet-list', 'ordered-list', 'task-list', 'blockquote', 'code-block', 'horizontal-rule', 'media', 'embed', 'table', 'details', 'math', 'emoji', 'find', 'source', 'fullscreen']);
    });

    it('fits its toolbar and menus to the format and tools', function () {
        $text = (string) $this->blade('<x-ui::editor format="text" />');
        $comment = (string) $this->blade('<x-ui::editor :format="[\'bold\', \'link\']" :tools="[\'toolbar\', \'bubble-menu\']" />');

        expect(controls($text))->toBe(['undo', 'redo', 'find', 'fullscreen'])
            ->and($text)->not->toContain('data-editor-tools')->not->toContain('<template')->not->toContain('data-emoji')
            ->and(controls($comment))->toBe(['undo', 'redo', 'bold', 'clear-formatting', 'link'])
            ->and($comment)->toContain('<template data-template="bubble-text">')
            ->not->toContain('data-template="bubble-table"')
            ->not->toContain('data-template="slash"')
            ->not->toContain('data-slot="editor-find"');
    });

    it('lists in its slash menu the blocks and media the format has', function () {
        $html = (string) $this->blade('<x-ui::editor :format="[\'heading\', \'bullet-list\', \'image\', \'math\']" />');

        preg_match('~<template data-template="slash">(.*?)</template>~s', $html, $slash);
        preg_match_all('~data-keywords="([^"]+)"~', $slash[1], $items);

        expect($items[1])->toBe(['paragraph', 'heading-1', 'heading-2', 'heading-3', 'heading-4', 'heading-5', 'heading-6', 'bullet-list', 'media', 'math'])
            ->and($slash[1])->toContain('data-editor-action="open" data-editor-value="math"');
    });

    it('offers the palette, fonts, sizes and line heights', function () {
        $html = (string) $this->blade('<x-ui::editor format="basic" :tools="[\'toolbar\']" />');

        expect(substr_count($html, 'data-editor-command="color"'))->toBe(9)
            ->and(substr_count($html, 'data-editor-command="highlight"'))->toBe(9)
            ->and(substr_count($html, 'data-editor-command="font-family"'))->toBe(13)
            ->and(substr_count($html, 'data-editor-command="font-size"'))->toBe(13)
            ->and(substr_count($html, 'data-editor-command="line-height"'))->toBe(8)
            ->and($html)->toContain('data-editor-command="color" data-editor-value="#dc2626" aria-pressed="false" aria-label="Red"><span style="color: #dc2626"><svg')
            ->toContain('role="menuitemradio" data-slot="dropdown-menu-item" data-variant="default" aria-checked="false" data-editor-command="font-family" data-editor-value="SimSun, &#039;Songti SC&#039;, serif" data-editor-label="SimSun"')
            ->toContain('data-editor-command="heading" data-editor-value="2" data-editor-label="Heading 2"')
            ->toContain('data-editor-command="heading" data-editor-value="6" data-editor-label="Heading 6"')
            ->and(substr_count($html, 'data-editor-command="heading"'))->toBe(6);
    });

    it('takes a toolbar of its own, its controls rendering only when the format has them', function () {
        $html = (string) $this->blade(<<<'BLADE'
            <x-ui::editor format="text">
                <x-ui::editor.toolbar class="justify-end">
                    <x-ui::editor.control name="undo" />
                    <x-ui::editor.control name="bold" />
                    <x-ui::editor.separator />
                    <x-ui::editor.control name="fullscreen" />
                </x-ui::editor.toolbar>
            </x-ui::editor>
            BLADE);

        expect($html)->toContain('<div data-slot="editor-toolbar" role="toolbar" aria-label="Formatting" class="justify-end" wire:ignore>')
            ->and(controls($html))->toBe(['undo', 'fullscreen']);
    });

    it('refuses a button or a menu it does not have', function (string $blade, string $message) {
        $this->blade($blade);
    })->throws(ViewException::class)->with([
        'a button' => ['<x-ui::editor.button command="blink" />', '[blink] is not an Editor button'],
        'a menu' => ['<x-ui::editor.menu name="blink" />', '[blink] is not an Editor menu'],
    ]);

    it('keeps the HTML in a hidden input for forms, and shows the disabled state', function () {
        $this->blade('<x-ui::editor name="body" default-value="<p>Hi</p>" disabled />')
            ->assertSee('data-name="body" data-disabled="data-disabled"', false)
            ->assertSee('<input type="hidden" name="body" value="&lt;p&gt;Hi&lt;/p&gt;">', false);
    });

    it('validates inside a field', function () {
        $this->withViewErrors(['body' => 'Write something.'])
            ->blade('<x-ui::field><x-ui::editor id="body" wire:model="body" /></x-ui::field>')
            ->assertSee('aria-invalid="true" aria-describedby="body-error" id="body" wire:model="body">', false)
            ->assertSeeInOrder(['</eso-editor>', 'Write something.'], false);
    });

    it('translates its labels', function () {
        app()->setLocale('zh_CN');

        $this->blade('<x-ui::editor />')
            ->assertSee('data-label="富文本编辑器"', false)
            ->assertSee('aria-label="格式"', false)
            ->assertSee('aria-label="粗体"', false)
            ->assertSee('placeholder="粘贴链接…"', false)
            ->assertSee('&quot;expand&quot;:&quot;展开&quot;', false)
            ->assertSee('>宋体</span>', false);
    });

    it('loads its module once per page', function () {
        Route::get('/editors', fn () => Blade::render('<html><head></head><body><x-ui::editor /><x-ui::editor /></body></html>'));

        $html = $this->get('/editors')->getContent();

        expect(substr_count($html, '<script type="module" src="http://localhost/esoboy-ui/editor.js?id=7777dddd8888"></script>'))->toBe(1);
    });

    it('renders what a Livewire property holds, sanitized', function () {
        Livewire::test(Posts::class)
            ->call('load', '<p>Hi<img src="x" onerror="alert(1)"></p>')
            ->assertSet('body', '<p>Hi<img src="x" onerror="alert(1)"></p>')
            ->assertSeeHtml('<div data-slot="editor-content" wire:ignore><p>Hi<img src="x" /></p></div>');
    });

    it('shows rich text as written, sanitized to its format', function () {
        $this->blade('<x-ui::rich-text class="prose" html="<h2 style=\'color:red\'>Hi</h2><p>a<script>x</script></p>" />')
            ->assertSee('<div data-slot="rich-text" class="prose"><h2>Hi</h2><p>a</p></div>', false);

        $this->blade('<x-ui::rich-text format="text" html="<h2>Hi</h2><p><b>a</b></p>" />')
            ->assertSee('<div data-slot="rich-text">Hi<p>a</p></div>', false);
    });
});

describe('sanitizing', function () {
    it('keeps what the editor writes', function () {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3><h4>D</h4><h5>E</h5><h6>F</h6><p>Text <strong>b</strong> <em>i</em> <u>u</u> <s>s</s> <code>c</code> <sup>2</sup> <sub>2</sub><br />line</p>'
            .'<ul><li><p>One</p></li></ul><ol start="3"><li><p>Three</p></li></ol><blockquote><p>Q</p></blockquote><pre><code>a &lt; b</code></pre><hr />'
            .'<details><summary>More</summary><div><p>Hidden</p></div></details>';

        expect(RichText::sanitize($html))->toBe($html);
    });

    it('keeps the styles the editor writes, and only their values', function () {
        $html = '<p style="text-align: center; margin-left: 4em; text-indent: 2em; line-height: 1.75;">'
            .'<span style="color: rgb(220, 38, 38); font-family: SimSun, &quot;Songti SC&quot;, serif; font-size: 18px;">a</span> '
            .'<mark style="background-color: rgb(254, 240, 138); color: rgb(23, 23, 23);">b</mark></p>';

        expect(RichText::sanitize($html))->toBe('<p style="text-align:center;margin-left:4em;text-indent:2em;line-height:1.75"><span style="color:#dc2626;font-family:SimSun, &#039;Songti SC&#039;, serif;font-size:18px">a</span> <mark style="background-color:#fef08a;color:#171717">b</mark></p>')
            ->and(RichText::sanitize('<p style="text-align: start; margin-left: 3em; line-height: 1.6; position: fixed">a <span style="color: #123456; font-size: 17px; background: url(x)">b</span></p>'))
            ->toBe('<p>a b</p>');
    });

    it('keeps styles by the format', function () {
        $html = '<h2 style="text-align:center">A</h2><p style="text-indent:2em"><span style="color:#dc2626;font-size:18px"><strong>b</strong></span></p>';

        expect(RichText::sanitize($html, ['heading', 'font-size']))->toBe('<h2>A</h2><p><span style="font-size:18px">b</span></p>')
            ->and(RichText::sanitize($html, 'text'))->toBe('A<p>b</p>')
            ->and(Ui::sanitize($html, 'basic'))->toBe('<h2 style="text-align:center">A</h2><p style="text-indent:2em"><span style="color:#dc2626;font-size:18px"><strong>b</strong></span></p>');
    });

    it('keeps task lists as disabled boxes, never fields', function () {
        $html = '<ul style="list-style-type: none; padding-left: 0px;"><li style="display: flex; align-items: baseline; gap: 0.5em;"><input type="text" name="admin" value="1" checked="checked" form="f" /><div><p>Done</p></div></li></ul>';

        expect(RichText::sanitize($html))->toBe('<ul style="list-style-type:none;padding-left:0"><li style="display:flex;align-items:baseline;gap:0.5em"><input type="checkbox" checked="checked" disabled="disabled" /><div><p>Done</p></div></li></ul>')
            ->and(RichText::sanitize('<p><input type="text" name="admin" value="1"></p>', 'basic'))->toBe('<p><input type="checkbox" disabled="disabled" /></p>')
            ->and(RichText::sanitize('<p><input type="text" name="admin"></p>', 'text'))->toBe('<p></p>');
    });

    it('keeps media and files on web addresses, as players', function () {
        $html = '<img src="/a.png" alt="A" width="1200" height="800" style="display: block; width: 50%; max-width: 100%; height: auto; margin-left: auto; margin-right: auto;">'
            .'<img src="data:image/png;base64,AAAA" style="width: 50%"><img src="javascript:alert(1)">'
            .'<video src="https://cdn.test/v.mp4" poster="/p.jpg" autoplay onplay="x()" style="display: block; width: 480px;"></video>'
            .'<audio src="/a.mp3" title="Song" loop></audio><audio src="data:audio/wav;base64,AA"></audio>'
            .'<a href="/f.pdf" download="f.pdf" type="application/pdf" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 8px; color: inherit; text-decoration: none;"><span>📎</span><span>f.pdf</span><span style="opacity: 0.6;">1 KB</span></a>';

        expect(RichText::sanitize($html))->toBe(
            '<img src="/a.png" alt="A" width="1200" height="800" style="display:block;width:50%;max-width:100%;height:auto;margin-left:auto;margin-right:auto" />'
            .'<video src="https://cdn.test/v.mp4" poster="/p.jpg" style="display:block;width:480px" controls="controls" preload="metadata"></video>'
            .'<audio src="/a.mp3" title="Song" controls="controls" preload="metadata"></audio>'
            .'<a href="/f.pdf" download="f.pdf" type="application/pdf" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(128,128,128,0.4);border-radius:8px;color:inherit;text-decoration:none" rel="noopener noreferrer nofollow">📎f.pdf<span style="opacity:0.6">1 KB</span></a>'
        );
    });

    it('plays embeds from known platforms only', function () {
        Ui::embedPlatform('acme', '^https://acme\.test/v/(\w+)', 'https://player.acme.test/$1');

        $style = 'style="display: block; width: 100%; max-width: 100%; height: auto; aspect-ratio: 16 / 9; border: 0px;"';

        expect(RichText::sanitize("<iframe src=\"https://www.youtube-nocookie.com/embed/abc\" {$style} onload=\"x()\"></iframe><iframe src=\"https://player.acme.test/1\"></iframe><iframe src=\"https://evil.test/x\"></iframe><iframe src=\"http://player.bilibili.com/player.html\"></iframe>"))
            ->toBe('<iframe src="https://www.youtube-nocookie.com/embed/abc" style="display:block;width:100%;max-width:100%;height:auto;aspect-ratio:16/9;border:0" allowfullscreen="allowfullscreen" loading="lazy"></iframe><iframe src="https://player.acme.test/1" allowfullscreen="allowfullscreen" loading="lazy"></iframe>')
            ->and(RichText::sanitize('<iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe><p>a</p>', 'basic'))->toBe('<p>a</p>');
    });

    it('keeps tables with their widths and cell styles', function () {
        $cell = 'border: 1px solid rgba(128, 128, 128, 0.4); padding: 6px 10px; vertical-align: top;';
        $html = "<div style=\"overflow-x: auto;\"><table style=\"border-collapse: collapse; width: 360px;\"><colgroup><col style=\"width: 120px;\" /><col style=\"min-width: 48px;\" /></colgroup><tbody><tr><th colspan=\"2\" style=\"{$cell} background-color: rgba(128, 128, 128, 0.1); text-align: left;\"><p>H</p></th></tr><tr><td style=\"{$cell} color: red;\"><p>1</p></td><td style=\"{$cell}\"><p>2</p></td></tr></tbody></table></div>";

        expect(RichText::sanitize($html))->toBe('<div style="overflow-x:auto"><table style="border-collapse:collapse;width:360px"><colgroup><col style="width:120px" /><col style="min-width:48px" /></colgroup><tbody><tr><th colspan="2" style="border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top;background-color:rgba(128,128,128,0.1);text-align:left"><p>H</p></th></tr><tr><td style="border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top"><p>1</p></td><td style="border:1px solid rgba(128,128,128,0.4);padding:6px 10px;vertical-align:top"><p>2</p></td></tr></tbody></table></div>');
    });

    it('keeps formulas as MathML, their LaTeX with them', function () {
        $math = '<math display="block"><semantics><mrow><msup><mi mathvariant="normal">e</mi><mn>2</mn></msup></mrow><annotation encoding="application/x-tex">\mathrm{e}^2</annotation></semantics></math>';

        expect(RichText::sanitize($math))->toBe($math)
            ->and(RichText::sanitize('<p>a</p>'.$math, 'basic'))->toBe('<p>a</p>');
    });

    it('keeps web, mail and phone links, made safe', function () {
        expect(RichText::sanitize('<p><a href="https://laravel.com" target="_blank">a</a> <a href="mailto:x@y.dev">b</a> <a href="tel:+861">c</a> <a href="/docs">d</a> <a href="javascript:alert(1)">e</a> <a href="data:text/html,x">f</a></p>'))
            ->toBe('<p><a href="https://laravel.com" target="_blank" rel="noopener noreferrer nofollow">a</a> <a href="mailto:x&#64;y.dev" rel="noopener noreferrer nofollow">b</a> <a href="tel:&#43;861" rel="noopener noreferrer nofollow">c</a> <a href="/docs" rel="noopener noreferrer nofollow">d</a> <a rel="noopener noreferrer nofollow">e</a> <a rel="noopener noreferrer nofollow">f</a></p>');
    });

    it('drops scripts, frames and attributes, and unwraps the rest keeping its text', function () {
        expect(RichText::sanitize('<p class="x" style="color:red" onclick="steal()">Hi <span data-a="1">there</span><img src="x" onerror="alert(1)"></p><script>alert(2)</script><iframe src="https://evil.test"></iframe><svg><circle /></svg><section><div>kept</div></section><form><button>Go</button></form>'))
            ->toBe('<p>Hi there<img src="x" /></p><div>kept</div>');
    });

    it('turns nothing into an empty string and keeps long HTML whole', function () {
        $long = str_repeat('<p>abcdefghij</p>', 3000);

        expect(RichText::sanitize(null))->toBe('')
            ->and(RichText::sanitize('   '))->toBe('')
            ->and(RichText::sanitize($long))->toBe($long)
            ->and(Ui::sanitize('<p>a<script>x</script></p>'))->toBe('<p>a</p>');
    });

    it('gives the text of rich text', function () {
        $html = '<h2>Title</h2><p>One<br>two <strong>bold</strong> &amp; more</p><ul><li><p>Item</p></li></ul><table><tbody><tr><td><p>a</p></td><td><p>b</p></td></tr></tbody></table><p>Formula <math><semantics><mi>x</mi><annotation encoding="application/x-tex">x^2</annotation></semantics></math></p><script>alert(1)</script>';

        expect(Ui::plainText($html))->toBe("Title\nOne\ntwo bold & more\nItem\n\na\tb\nFormula x^2")
            ->and(Ui::plainText(null))->toBe('');
    });

    it('cleans a #[Sanitize] property as the browser sets it, to its format, before updated hooks read it', function () {
        Livewire::test(Posts::class)
            ->set('body', '<p onclick="steal()">Hi</p><script>alert(1)</script>')
            ->assertSet('body', '<p>Hi</p>')
            ->assertSet('seen', '<p>Hi</p>')
            ->set('intro', '<p>A<img src="/a.png"></p><table><tbody><tr><td>B</td></tr></tbody></table>')
            ->assertSet('intro', '<p>A</p>B')
            ->set('plain', '<script>kept</script>')
            ->assertSet('plain', '<script>kept</script>');
    });

    it('cleans a #[Sanitize] property of a form object', function () {
        Livewire::test(Posts::class)
            ->set('form.body', '<p>Hi<iframe src="https://evil.test"></iframe></p>')
            ->assertSet('form.body', '<p>Hi</p>');
    });
});

describe('the server', function () {
    it('answers editor-media with attachments, or inserts HTML, through Ui::editor()', function () {
        Livewire::test(Posts::class)
            ->call('attach')
            ->assertDispatched('ui-editor', id: 'body', action: 'insert', attachments: [['url' => '/a.png', 'name' => 'a.png', 'mime' => 'image/png']], html: null)
            ->call('template')
            ->assertDispatched('ui-editor', id: 'body', action: 'insert-content', attachments: [], html: '<p>Hello</p>');
    });
});
