<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo;

use BasekitLaravel\BasekitLaravelSeo\Components\Head;
use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

final class BasekitLaravelSeoServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basekit-laravel-seo.php', 'basekit-laravel-seo');

        $this->app->singleton(Sitemap::class, fn (): Sitemap => new Sitemap);

        $this->app->scoped(SeoManager::class, fn (Container $app): SeoManager => new SeoManager($app));
    }

    public function boot(): void
    {
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
    }
}
