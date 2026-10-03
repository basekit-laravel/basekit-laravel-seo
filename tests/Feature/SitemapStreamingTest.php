<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapAggregator;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\DuplicateSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\PageSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\SeriesSitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs\TrackingSitemapProvider;

it('streams provider entries lazily so a huge sitemap is never fully materialised', function (): void {
    TrackingSitemapProvider::reset();

    app()->instance(SeriesSitemapProvider::class, new SeriesSitemapProvider(1));
    app()->instance(TrackingSitemapProvider::class, new TrackingSitemapProvider('second'));
    app()->tag(
        [SeriesSitemapProvider::class, TrackingSitemapProvider::class],
        SitemapProvider::PROVIDER_TAG,
    );

    $stream = app(SitemapAggregator::class)->stream();

    // Consume only the first entry...
    $stream->rewind();

    expect($stream->current()->loc)->toBe('https://example.test/pages/1')
        // ...the second provider must not have been touched yet.
        ->and(TrackingSitemapProvider::$started)->toBeFalse();

    // Advancing past provider one's only entry pulls in provider two.
    $stream->next();

    expect(TrackingSitemapProvider::$started)->toBeTrue()
        ->and($stream->current()->loc)->toBe('https://example.test/second/1');
});

it('still deduplicates locations while streaming', function (): void {
    app()->tag([
        DuplicateSitemapProvider::class,
        PageSitemapProvider::class,
    ], SitemapProvider::PROVIDER_TAG);

    $locations = [];

    foreach (app(SitemapAggregator::class)->stream() as $entry) {
        $locations[] = $entry->loc;
    }

    expect($locations)->toBe(array_values(array_unique($locations)));
});
