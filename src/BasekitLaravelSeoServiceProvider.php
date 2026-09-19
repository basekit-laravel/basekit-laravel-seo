<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo;

use BasekitLaravel\BasekitLaravelSeo\Services\Sitemap;
use Illuminate\Support\ServiceProvider;

final class BasekitLaravelSeoServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basekit-laravel-seo.php', 'basekit-laravel-seo');

        $this->app->singleton(Sitemap::class, fn (): Sitemap => new Sitemap);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'basekit-laravel-seo');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/basekit-laravel-seo'),
        ], 'basekit-laravel-seo-views');

        $this->publishes([
            __DIR__.'/../config/basekit-laravel-seo.php' => config_path('basekit-laravel-seo.php'),
        ], 'basekit-laravel-seo-config');
    }
}
