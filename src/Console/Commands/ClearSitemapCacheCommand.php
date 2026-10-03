<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Console\Commands;

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use Illuminate\Console\Command;

final class ClearSitemapCacheCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'basekit-seo:sitemap:clear';

    /**
     * @var string
     */
    protected $description = 'Remove every cached basekit-laravel-seo sitemap document so the next request regenerates it';

    public function handle(SitemapCache $cache): int
    {
        if (! $cache->isEnabled()) {
            $this->components->warn('Sitemap caching is disabled; there is nothing cached to clear.');

            return self::SUCCESS;
        }

        $cache->clear();

        $this->components->info('Cached sitemap documents cleared.');

        return self::SUCCESS;
    }
}
