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
    }
}
