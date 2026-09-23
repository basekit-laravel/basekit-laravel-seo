<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;

it('builds an Open Graph object and serializes non-null values', function (): void {
    $og = OpenGraph::make()
        ->withSiteName('Basekit')
        ->withTitle('A page')
        ->withImage('https://example.test/og.png');

    expect($og->toArray())->toBe([
        'title' => 'A page',
        'image' => 'https://example.test/og.png',
        'site_name' => 'Basekit',
    ]);
});

it('omits unset values from the array', function (): void {
    expect(OpenGraph::make()->withSiteName('Basekit')->toArray())
        ->toBe(['site_name' => 'Basekit']);
});

it('validates image and url against canonical URL safety rules', function (string $url): void {
    expect(fn (): OpenGraph => OpenGraph::make()->withImage($url))->toThrow(InvalidArgumentException::class)
        ->and(fn (): OpenGraph => OpenGraph::make()->withUrl($url))->toThrow(InvalidArgumentException::class);
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:image/png;base64,iVBOR',
    'no-scheme' => 'example.test/og.png',
]);

it('accepts absolute http(s) image and url values', function (): void {
    $og = OpenGraph::make()->withImage('https://cdn.example.test/og.png')->withUrl('https://example.test/article');

    expect($og->toArray())->toBe([
        'image' => 'https://cdn.example.test/og.png',
        'url' => 'https://example.test/article',
    ]);
});

it('builds from an array using snake and camel case keys', function (): void {
    $og = OpenGraph::fromArray([
        'title' => 'From array',
        'site_name' => 'Acme',
        'url' => 'https://example.test/article',
    ]);

    expect($og->title)->toBe('From array')
        ->and($og->siteName)->toBe('Acme')
        ->and($og->url)->toBe('https://example.test/article');
});

it('is immutable', function (): void {
    $og = OpenGraph::make()->withTitle('Original');

    expect($og->withTitle('Changed')->title)->toBe('Changed')
        ->and($og->title)->toBe('Original');
});
