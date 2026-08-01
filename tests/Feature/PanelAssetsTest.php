<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Filament serves its CSS and JS from public/, published by `filament:assets`
 * (which composer runs on install via `filament:upgrade`). If that has not run
 * — or has run for a different Filament version — the panel still returns 200
 * while every stylesheet and script 404s, so the page arrives unstyled and
 * inert. Nothing else in the suite notices, because the HTML is fine.
 *
 * This asserts that every local asset the panel asks for is actually on disk.
 */
class PanelAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_asset_the_login_page_references_exists(): void
    {
        $this->assertAssetsExistOn($this->get('/admin/login')->assertOk()->getContent());
    }

    public function test_every_asset_the_dashboard_references_exists(): void
    {
        $this->actingAs($this->panelUser());

        $this->assertAssetsExistOn($this->get('/admin')->assertOk()->getContent());
    }

    private function assertAssetsExistOn(string $html): void
    {
        $assets = $this->localAssetPaths($html);

        $this->assertNotEmpty(
            $assets,
            'No local CSS or JS was found on the page — the extraction below is wrong, not the assets.',
        );

        $missing = array_values(array_filter(
            $assets,
            fn (string $path): bool => ! $this->isServable($path),
        ));

        $this->assertSame([], $missing, sprintf(
            "The panel references %d asset(s) that nothing can serve:\n  %s\nRun `php artisan filament:assets`.",
            count($missing),
            implode("\n  ", $missing),
        ));
    }

    /**
     * An asset is fine if it is a real file under public/, or if something has
     * registered a route for it — Livewire serves its own JS that way rather
     * than publishing a file.
     */
    private function isServable(string $path): bool
    {
        if (file_exists(public_path($path))) {
            return true;
        }

        try {
            app('router')->getRoutes()->match(Request::create('/'.$path, 'GET'));

            return true;
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    }

    /**
     * Pull out same-origin .css/.js references, dropping the cache-busting
     * query string and any absolute URL host.
     *
     * @return list<string>
     */
    private function localAssetPaths(string $html): array
    {
        preg_match_all('/(?:href|src)="([^"]+\.(?:css|js)(?:\?[^"]*)?)"/i', $html, $matches);

        $paths = [];

        foreach ($matches[1] as $url) {
            $url = html_entity_decode($url, ENT_QUOTES);
            $path = parse_url($url, PHP_URL_PATH);

            if (! is_string($path)) {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if ($host !== null && $host !== parse_url(config('app.url'), PHP_URL_HOST)) {
                continue;
            }

            $paths[] = ltrim($path, '/');
        }

        return array_values(array_unique($paths));
    }
}
