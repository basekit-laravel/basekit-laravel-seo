<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Concerns;

use Override;

/**
 * Boots an app whose package routes carry middleware, which is only readable
 * when the configuration is in place before the service provider registers the
 * route file.
 */
trait AppliesSeoRouteMiddleware
{
    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('basekit-laravel-seo.sitemap.middleware', ['throttle:5,1']);
    }
}
