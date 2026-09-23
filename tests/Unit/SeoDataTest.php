<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\SeoData;
use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

it('defaults every value to null and empty lists', function (): void {
    $data = SeoData::make();

    expect($data->title)->toBeNull()
        ->and($data->description)->toBeNull()
        ->and($data->canonicalUrl)->toBeNull()
        ->and($data->robots)->toBeNull()
        ->and($data->openGraph)->toBeNull()
        ->and($data->twitter)->toBeNull()
        ->and($data->locale)->toBeNull()
        ->and($data->alternates)->toBe([])
        ->and($data->schemas)->toBe([]);
});

it('is immutable and withers return new instances', function (): void {
    $base = SeoData::make()->withTitle('Original');

    $changed = $base->withTitle('Changed');

    expect($base->title)->toBe('Original')
        ->and($changed->title)->toBe('Changed')
        ->and($changed)->not->toBe($base);
});

it('accepts string canonical URLs and robots values', function (): void {
    $data = SeoData::make()
        ->withCanonicalUrl('https://example.test/page')
        ->withRobots('noindex, nofollow');

    expect($data->canonicalUrl?->toString())->toBe('https://example.test/page')
        ->and($data->robots?->toString())->toBe('noindex, nofollow');
});

it('appends alternates and schemas without losing earlier values', function (): void {
    $data = SeoData::make()
        ->withAlternate('en', 'https://example.test/page')
        ->withAlternate('de', 'https://example.test/de')
        ->withSchema(WebPageSchema::make()->name('Home'));

    expect($data->alternates)->toHaveCount(2)
        ->and($data->schemas)->toHaveCount(1)
        ->and($data->alternates[0]->hreflang)->toBe('en')
        ->and($data->schemas[0])->toBeInstanceOf(WebPageSchema::class);
});

it('accepts Open Graph and Twitter as raw arrays', function (): void {
    $data = SeoData::make()
        ->withOpenGraph(['title' => 'OG title', 'site_name' => 'Acme'])
        ->withTwitter(['card' => 'summary']);

    expect($data->openGraph)->toBeInstanceOf(OpenGraph::class)
        ->and($data->openGraph?->title)->toBe('OG title')
        ->and($data->twitter?->card)->toBe('summary');
});

it('can clear a value back to null', function (): void {
    $data = SeoData::make()->withTitle('Draft')->withTitle(null);

    expect($data->title)->toBeNull();
});

it('serializes to an array and round-trips through fromArray', function (): void {
    $data = SeoData::make()
        ->withTitle('A page')
        ->withDescription('Page description')
        ->withCanonicalUrl('https://example.test/page')
        ->withLocale('en')
        ->withAlternate('de', 'https://example.test/de');

    $array = $data->toArray();

    expect($array)->toMatchArray([
        'title' => 'A page',
        'description' => 'Page description',
        'canonical_url' => 'https://example.test/page',
        'locale' => 'en',
    ]);

    expect($array['alternates'])->toBe([
        ['hreflang' => 'de', 'url' => 'https://example.test/de'],
    ]);

    $restored = SeoData::fromArray($array);

    expect($restored->title)->toBe('A page')
        ->and($restored->canonicalUrl?->toString())->toBe('https://example.test/page')
        ->and($restored->alternates)->toHaveCount(1)
        ->and($restored->alternates[0]->hreflang)->toBe('de');
});

it('is JSON serializable', function (): void {
    $json = json_encode(SeoData::make()->withTitle('A page'));

    expect($json)->toBe(json_encode([
        'title' => 'A page',
        'alternates' => [],
        'schemas' => [],
    ]));
});

it('rejects unsafe canonical URLs', function (): void {
    expect(fn (): SeoData => SeoData::make()->withCanonicalUrl('javascript:alert(1)'))
        ->toThrow(InvalidArgumentException::class);
});
