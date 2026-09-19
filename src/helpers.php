<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\SeoManager;

if (! function_exists('seo')) {
    /**
     * Resolve the SEO manager from the container.
     */
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}
