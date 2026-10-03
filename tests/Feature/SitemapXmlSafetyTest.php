<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

it('strips control characters even when a location is not valid UTF-8', function (): void {
    // The control-character strip must not be implemented with a /u-flagged
    // preg_replace(): that returns null for invalid UTF-8, silently skipping the
    // strip and letting raw control characters into the document.
    $location = "https://example.test/pages/1\x0B\x1F?bad=\xFF";

    app()->bind(SitemapProvider::class, fn () => new class($location) implements SitemapProvider
    {
        public function __construct(private string $location) {}

        public function entries(): iterable
        {
            yield new SitemapEntry($this->location);
        }
    });

    $content = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($content)->not->toMatch('/[\x00-\x08\x0B\x0C\x0E-\x1F]/')
        // ...and the document is still parseable XML.
        ->and(simplexml_load_string($content))->not->toBeFalse();
});
