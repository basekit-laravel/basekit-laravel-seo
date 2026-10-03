<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo;

use BasekitLaravel\BasekitLaravelSeo\Components\Head;
use BasekitLaravel\BasekitLaravelSeo\Console\Commands\ClearSitemapCacheCommand;
use BasekitLaravel\BasekitLaravelSeo\Console\Commands\WarmSitemapCacheCommand;
use BasekitLaravel\BasekitLaravelSeo\Services\CanonicalUrlResolver;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapAggregator;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapChunker;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapGenerator;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use BasekitLaravel\BasekitLaravelSeo\Support\ConfigValidator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Override;

final class BasekitLaravelSeoServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basekit-laravel-seo.php', 'basekit-laravel-seo');

        $this->app->singleton(CanonicalUrlResolver::class);
        $this->app->singleton(SitemapAggregator::class);

        $this->app->singleton(SitemapPaths::class, fn (): SitemapPaths => SitemapPaths::fromConfig());
        $this->app->singleton(SitemapRenderer::class);
        $this->app->singleton(SitemapChunker::class, fn (Container $app): SitemapChunker => new SitemapChunker(
            $app->make(SitemapRenderer::class),
            (int) config('basekit-laravel-seo.sitemap.max_urls', 50_000),
            (int) config('basekit-laravel-seo.sitemap.max_bytes', 50 * 1024 * 1024),
        ));
        $this->app->singleton(SitemapCache::class, fn (Container $app): SitemapCache => new SitemapCache(
            $app->make('cache'),
        ));

        // Scoped, not singleton: the generator memoises the catalog it built for
        // the current request, and that memo must never survive into the next
        // one under a long-running worker.
        $this->app->scoped(SitemapGenerator::class);

        $this->app->scoped(SeoManager::class, fn (Container $app): SeoManager => new SeoManager($app));
    }

    public function boot(): void
    {
        // Fail fast on a misconfigured deploy instead of 500ing on the first
        // crawler request.
        ConfigValidator::validate();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'basekit-laravel-seo');

        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade): void {
            $blade->component(Head::class, 'basekit-laravel-seo::head');
        });

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/basekit-laravel-seo'),
        ], 'basekit-laravel-seo-views');

        $this->publishes([
            __DIR__.'/../config/basekit-laravel-seo.php' => config_path('basekit-laravel-seo.php'),
        ], 'basekit-laravel-seo-config');

        $this->commands([
            ClearSitemapCacheCommand::class,
            WarmSitemapCacheCommand::class,
        ]);

        if ((bool) config('basekit-laravel-seo.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
