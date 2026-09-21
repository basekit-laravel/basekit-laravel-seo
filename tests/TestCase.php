<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests;

use BasekitLaravel\BasekitLaravelSeo\BasekitLaravelSeoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            BasekitLaravelSeoServiceProvider::class,
        ];
    }

    #[\Override]
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.url', 'https://example.test');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('cache.default', 'array');
    }
}

/**
 * A trait that boots an otherwise-identical app with the package disabled, so
 * the sitemap and robots routes are never registered at boot.
 */
trait DisablesSeo
{
    #[\Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('basekit-laravel-seo.enabled', false);
    }
}
