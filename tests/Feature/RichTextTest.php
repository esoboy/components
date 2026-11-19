<?php

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders HTML and Markdown strings through the public rich text entry', function (string $content, string $type) {
    $html = Blade::render('<x-ui::rich-text :value="$content" :input-type="$type" />', compact('content', 'type'));
    expect($html)->toContain('<strong>世界</strong>', '<h2')->not->toContain('&lt;strong&gt;');
})->with([
    ['<h2>标题</h2><p>Hello <strong>世界</strong></p>', 'html'],
    ["## 标题\n\nHello **世界**", 'markdown'],
]);

it('preserves supported rich attributes and embedded HTML in Markdown', function () {
    $content = "## 标题\n\n<p style=\"text-align: center\"><span style=\"color: #cc0000; font-size: 24px; font-family: Georgia; line-height: 1.8\">样式</span><u>下划线</u><sub>2</sub></p>\n\n<table><tbody><tr><td colspan=\"2\" colwidth=\"120,120\">合并</td></tr></tbody></table>\n\n<details open><summary>详情</summary><div data-type=\"detailsContent\"><p>内容</p></div></details>";
    $html = Blade::render('<x-ui::rich-text :value="$content" input-type="markdown" />', compact('content'));
    expect($html)->toContain('text-align: center', 'font-size: 24px', 'font-family: Georgia', 'line-height: 1.8', 'colspan="2"', 'colwidth="120,120"', '<details', '<summary>详情</summary>', '<sub>2</sub>');
});

it('sanitizes active HTML and unsafe styles without losing safe content', function () {
    $content = '<p onclick="alert(1)" style="position:fixed;color:red;background-image:url(javascript:alert(1))">安全<strong>文本</strong></p><script>alert(1)</script><img src="javascript:alert(1)" onerror="alert(1)"><iframe src="data:text/html,invalid"></iframe><iframe srcdoc="inline"></iframe><a href="javascript:alert(1)">链接</a>';
    $html = Blade::render('<x-ui::rich-text :value="$content" input-type="html" />', compact('content'));
    expect($html)->toContain('安全<strong>文本</strong>', 'color: red')->not->toContain('onclick', 'onerror', '<script', 'javascript:', 'position:', 'background-image', '<iframe', 'srcdoc');
});

it('keeps project media URLs and makes task checkboxes read only', function () {
    $content = '<img src="https://project-cdn.example/image.png"><audio src="/audio.mp3" controls></audio><ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><input type="checkbox" checked><span></span></label><div><p>完成</p></div></li></ul>';
    $html = Blade::render('<x-ui::rich-text :value="$content" />', compact('content'));
    expect($html)->toContain('https://project-cdn.example/image.png', '<audio', 'data-checked="true"', 'disabled')->not->toContain('srcdoc');
});

it('rejects structured content arrays', function () {
    expect(fn () => Blade::render('<x-ui::rich-text :value="[]" />'))->toThrow(ViewException::class);
});

it('preserves visual size presets independently from file metadata', function (string $type) {
    $content = '<img src="/photo.jpg" data-display-size="lg" data-size="8192" data-file-width="1200">'
        ."\n\n".'<video src="/clip.mp4" data-display-size="full" controls></video>'
        ."\n\n".'<img src="/legacy.jpg" data-display-size="unknown" width="240">'
        ."\n\n".'<audio src="/sound.mp3" data-display-size="sm" controls></audio>';
    $html = Blade::render('<x-ui::rich-text :value="$content" :input-type="$type" />', compact('content', 'type'));
    $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    expect($doc->querySelectorAll('[data-display-size]')->length)->toBe(2)
        ->and($doc->querySelector('img[data-display-size="lg"]')->getAttribute('data-size'))->toBe('8192')
        ->and($doc->querySelector('img[data-display-size="lg"]')->getAttribute('data-file-width'))->toBe('1200')
        ->and($doc->querySelector('video')->getAttribute('data-display-size'))->toBe('full')
        ->and($doc->querySelector('img[src="/legacy.jpg"]')->getAttribute('width'))->toBe('240');
})->with(['html', 'markdown']);

it('renders each stored visual and attachment metadata through the Blade rich text entry', function () {
    $content = '<div data-type="mediaRow"><img src="/one.jpg" width="160"><video src="/two.mp4" width="180" controls></video></div><a data-type="file" href="/guide.pdf" data-name="指南.pdf" data-ext="pdf" data-size="2560" title="&lt;阅读&gt;">指南.pdf</a><a href="/page">正文链接</a>';
    $html = Blade::render('<x-ui::rich-text :value="$content" />', compact('content'));
    expect($html)->not->toContain('data-type="mediaRow"')->toContain('src="/one.jpg"', 'src="/two.mp4"', 'width="160"', 'width="180"', 'download="指南.pdf"', '<span>&lt;阅读&gt;</span>', '<span>指南.pdf · 2.5 KB</span>', '>正文链接</a>');
});

it('retains third-party frames with normalized playback attributes in both formats', function (string $type) {
    $content = '<iframe src="//open.iqiyi.com/developer/player_js/coopPlayerIndex.html?vid=example&amp;tvId=12" title="湖畔短片" width="720" height="405" sandbox="allow-forms" srcdoc="inline" onload="ignored"></iframe>'
        ."\n\n".'<iframe src="https://www.youtube.com/embed/M7lc1UVf-VE" width="100%" height="-20"></iframe>'
        ."\n\n".'<iframe src="https://video.example/player"></iframe>';
    $html = Blade::render('<x-ui::rich-text :value="$content" :input-type="$type" />', compact('content', 'type'));
    $frames = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8')->querySelectorAll('iframe');
    expect($frames->length)->toBe(3);
    $iqiyi = $frames->item(0);
    expect($iqiyi->getAttribute('src'))->toBe('https://open.iqiyi.com/developer/player_js/coopPlayerIndex.html?vid=example&tvId=12')
        ->and($iqiyi->getAttribute('title'))->toBe('湖畔短片')
        ->and($iqiyi->getAttribute('width'))->toBe('720')
        ->and($iqiyi->getAttribute('height'))->toBe('405')
        ->and($iqiyi->getAttribute('sandbox'))->toContain('allow-same-origin')
        ->and($iqiyi->hasAttribute('allowfullscreen'))->toBeTrue();
    expect($frames->item(1)->getAttribute('width'))->toBe('640')
        ->and($frames->item(1)->getAttribute('height'))->toBe('360')
        ->and($frames->item(1)->getAttribute('title'))->toBe('Embedded content')
        ->and($frames->item(2)->getAttribute('sandbox'))->toBe('allow-scripts allow-presentation')
        ->and($html)->not->toContain('allow-forms', 'srcdoc', 'onload');
})->with(['html', 'markdown']);
