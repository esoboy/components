<?php

use Esoboy\Ui\Facades\Ui;
use Esoboy\Ui\Support\RichText;
use Esoboy\Ui\Tests\Fixtures\Posts;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\ViewException;
use Livewire\Livewire;

/*
 * Editor: Tiptap in the editor.js module, with HTML sanitized on the way in (the view)
 * and back (#[Sanitize], Ui::sanitize()).
 */

describe('the view', function () {
    it('renders the editor, the whole toolbar and the sanitized content', function () {
        $html = (string) $this->blade('<x-ui::editor wire:model="body" placeholder="Write" default-value="<p onclick=\'x\'>Hi <b>there</b></p><script>alert(1)</script>" />');

        expect($html)
            ->toContain('<eso-editor data-slot="editor" data-placeholder="Write" data-label="Rich text editor" wire:model="body">')
            ->toContain('<div data-slot="editor-toolbar" role="toolbar" aria-label="Formatting" wire:ignore>')
            ->toContain('data-variant="ghost" data-size="icon-sm" data-editor-command="undo" aria-label="Undo"><svg')
            ->toContain('data-editor-command="bold" aria-label="Bold" aria-pressed="false"><svg')
            ->toContain('data-editor-link="data-editor-link" aria-pressed="false" aria-label="Link">')
            ->toContain('<input type="url" data-slot="input" data-editor-link-input="data-editor-link-input" placeholder="Paste a link…" aria-label="Link">')
            ->toContain('data-editor-link-remove="data-editor-link-remove" aria-label="Remove link">')
            ->toContain('<div data-slot="editor-content" wire:ignore><p>Hi <b>there</b></p></div>')
            ->not->toContain('onclick')
            ->not->toContain('alert')
            ->and(substr_count($html, 'data-editor-command='))->toBe(15)
            ->and(substr_count($html, 'data-slot="separator"'))->toBe(4);
    });

    it('takes a toolbar of its own', function () {
        $html = (string) $this->blade(<<<'BLADE'
            <x-ui::editor>
                <x-ui::editor.toolbar class="justify-end">
                    <x-ui::editor.button command="bold" />
                    <x-ui::editor.separator />
                    <x-ui::editor.link />
                </x-ui::editor.toolbar>
            </x-ui::editor>
            BLADE);

        expect($html)
            ->toContain('<div data-slot="editor-toolbar" role="toolbar" aria-label="Formatting" class="justify-end" wire:ignore>')
            ->toContain('data-editor-command="bold"')
            ->toContain('data-editor-link=')
            ->and(substr_count($html, 'data-editor-command='))->toBe(1);
    });

    it('refuses a command it does not have', function () {
        $this->blade('<x-ui::editor.button command="table" />');
    })->throws(ViewException::class, '[table] is not an Editor command');

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
            ->assertSee('placeholder="粘贴链接…"', false);
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
            ->assertSeeHtml('<div data-slot="editor-content" wire:ignore><p>Hi</p></div>');
    });
});

describe('sanitizing', function () {
    it('keeps what the editor writes', function () {
        $html = '<h1>A</h1><h2>B</h2><h3>C</h3><p>Text <strong>b</strong> <em>i</em> <u>u</u> <s>s</s> <code>c</code><br />line</p>'
            .'<ul><li><p>One</p></li></ul><ol start="3"><li><p>Three</p></li></ol><blockquote><p>Q</p></blockquote><pre><code>a &lt; b</code></pre><hr />';

        expect(RichText::sanitize($html))->toBe($html);
    });

    it('keeps web, mail and phone links, made safe', function () {
        expect(RichText::sanitize('<p><a href="https://laravel.com" target="_blank">a</a> <a href="mailto:x@y.dev">b</a> <a href="tel:+861">c</a> <a href="/docs">d</a> <a href="javascript:alert(1)">e</a> <a href="data:text/html,x">f</a></p>'))
            ->toBe('<p><a href="https://laravel.com" target="_blank" rel="noopener noreferrer nofollow">a</a> <a href="mailto:x&#64;y.dev" rel="noopener noreferrer nofollow">b</a> <a href="tel:&#43;861" rel="noopener noreferrer nofollow">c</a> <a href="/docs" rel="noopener noreferrer nofollow">d</a> <a rel="noopener noreferrer nofollow">e</a> <a rel="noopener noreferrer nofollow">f</a></p>');
    });

    it('drops scripts, embeds and attributes, and unwraps the rest keeping its text', function () {
        expect(RichText::sanitize('<p class="x" style="color:red" onclick="steal()">Hi <span data-a="1">there</span><img src="x" onerror="alert(1)"></p><script>alert(2)</script><iframe src="https://evil.test"></iframe><svg><circle /></svg><section><div>kept</div></section>'))
            ->toBe('<p>Hi there</p><div>kept</div>');
    });

    it('turns nothing into an empty string and keeps long HTML whole', function () {
        $long = str_repeat('<p>abcdefghij</p>', 3000);

        expect(RichText::sanitize(null))->toBe('')
            ->and(RichText::sanitize('   '))->toBe('')
            ->and(RichText::sanitize($long))->toBe($long)
            ->and(Ui::sanitize('<p>a<script>x</script></p>'))->toBe('<p>a</p>');
    });

    it('cleans a #[Sanitize] property as the browser sets it, before updated hooks read it', function () {
        Livewire::test(Posts::class)
            ->set('body', '<p onclick="steal()">Hi</p><script>alert(1)</script>')
            ->assertSet('body', '<p>Hi</p>')
            ->assertSet('seen', '<p>Hi</p>')
            ->set('plain', '<script>kept</script>')
            ->assertSet('plain', '<script>kept</script>');
    });

    it('cleans a #[Sanitize] property of a form object', function () {
        Livewire::test(Posts::class)
            ->set('form.body', '<p>Hi<iframe src="https://evil.test"></iframe></p>')
            ->assertSet('form.body', '<p>Hi</p>');
    });
});
