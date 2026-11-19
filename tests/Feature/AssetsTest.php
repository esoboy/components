<?php

namespace Esoboy\Ui\Tests\Feature;

use Esoboy\Ui\Support\Assets;
use Esoboy\Ui\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;

final class AssetsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->usePublicPath(Storage::fake('editor-public')->path(''));
    }

    public static function assets(): array
    {
        return [['ui.js', 'text/javascript'], ['editor.js', 'text/javascript']];
    }

    #[DataProvider('assets')]
    public function test_assets_are_served_without_publication(string $file, string $type): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'laravel-assets', '--force' => true])->assertSuccessful();
        URL::useAssetOrigin('https://assets.example.test');

        $source = Assets::DIRECTORY.'/'.$file;
        $version = hash_file('xxh128', $source);
        $url = route('esoboy.ui.asset', ['file' => $file, 'version' => $version]);
        $this->assertSame($url, Assets::url($file));

        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', $type.'; charset=utf-8');
        $this->assertSame(realpath($source), $response->getFile()->getRealPath());
        $this->assertTrue($response->isImmutable());
        $this->get($url, ['If-None-Match' => $response->getEtag()])->assertStatus(304);
        $this->get(route('esoboy.ui.asset', ['file' => $file, 'version' => 'outdated']))->assertNotFound();
        $this->assertFileDoesNotExist(public_path(Assets::PUBLIC_DIRECTORY.'/'.$file));
    }

    public function test_explicit_publication_uses_the_static_url_only_for_a_matching_copy(): void
    {
        URL::useAssetOrigin('https://assets.example.test');
        $packageUrl = Assets::url('editor.js');
        $this->artisan('vendor:publish', ['--tag' => 'ui-assets'])->assertSuccessful();

        $published = public_path('vendor/esoboy/ui/editor.js');
        $this->assertFileEquals(dirname(__DIR__, 2).'/resources/dist/editor.js', $published);
        $this->assertSame('https://assets.example.test/vendor/esoboy/ui/editor.js?v='.hash_file('xxh128', $published), Assets::url('editor.js'));

        file_put_contents($published, 'previous release');
        $this->assertSame($packageUrl, Assets::url('editor.js'));
    }

    public function test_the_layout_directive_loads_the_common_script_once_per_render(): void
    {
        for ($render = 0; $render < 2; $render++) {
            $html = Blade::render("@uiScript @include('ui::assets') @uiScript");
            $this->assertSame(1, substr_count($html, Assets::url('ui.js')));
            $this->assertStringNotContainsString('editor.js', $html);
            $this->assertStringNotContainsString('<link', $html);
        }
        $this->get(route('esoboy.ui.asset', ['file' => 'ui.css', 'version' => 'anything']))->assertNotFound();
        $this->get(route('esoboy.ui.asset', ['file' => 'catalog.php', 'version' => 'anything']))->assertNotFound();
    }
}
