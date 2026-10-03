<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Console\Commands;

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapGenerator;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use Illuminate\Console\Command;
use Throwable;

final class WarmSitemapCacheCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'basekit-seo:sitemap:warm {--force : Regenerate even when a warm catalog is already cached}';

    /**
     * @var string
     */
    protected $description = 'Regenerate and cache every sitemap document so crawler traffic is served from cache';

    public function handle(SitemapCache $cache, SitemapGenerator $generator): int
    {
        if (! $cache->isEnabled()) {
            $this->components->error('Sitemap caching is disabled. Enable basekit-laravel-seo.sitemap.cache.enabled before warming.');

            return self::FAILURE;
        }

        try {
            // A warm catalog is left alone so this is safe to schedule; `--force`
            // bypasses the cache and always reruns the providers.
            if (! $this->option('force') && $cache->catalog() instanceof SitemapCatalog) {
                $this->components->info('Sitemap cache is already warm; nothing to do. Use --force to regenerate.');

                return self::SUCCESS;
            }

            $catalog = $this->option('force')
                ? $generator->regenerateNow()[0]
                : $generator->catalog();
        } catch (Throwable $e) {
            $this->components->error('Sitemap warm-up failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Warmed %d sitemap document(s) containing %d URL(s).',
            $catalog->count(),
            $catalog->totalEntries(),
        ));

        return self::SUCCESS;
    }
}
