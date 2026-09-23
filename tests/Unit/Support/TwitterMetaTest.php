<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\TwitterMeta;

it('defaults the card to summary_large_image', function (): void {
    $twitter = TwitterMeta::make()->withTitle('A page');

    expect($twitter->card)->toBe('summary_large_image')
        ->and($twitter->toArray())->toBe([
            'card' => 'summary_large_image',
            'title' => 'A page',
        ]);
});

it('normalizes the card to lowercase', function (): void {
    expect(TwitterMeta::make()->withCard('SUMMARY')->card)->toBe('summary');
});

it('validates the image against canonical URL safety rules', function (): void {
    expect(fn (): TwitterMeta => TwitterMeta::make()->withImage('javascript:alert(1)'))
        ->toThrow(InvalidArgumentException::class);
});

it('builds from an array', function (): void {
    $twitter = TwitterMeta::fromArray([
        'card' => 'summary',
        'creator' => '@author',
        'image' => 'https://example.test/tw.png',
    ]);

    expect($twitter->card)->toBe('summary')
        ->and($twitter->creator)->toBe('@author')
        ->and($twitter->image)->toBe('https://example.test/tw.png');
});

it('serializes non-null values only', function (): void {
    $twitter = TwitterMeta::make()->withCard('summary')->withSite('@acme');

    expect($twitter->toArray())->toBe([
        'card' => 'summary',
        'site' => '@acme',
    ]);
});
