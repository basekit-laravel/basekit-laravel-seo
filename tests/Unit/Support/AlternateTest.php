<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\Alternate;
use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;

it('builds an alternate with a normalized hreflang', function (): void {
    $alternate = Alternate::for('EN-US', 'https://example.test/page');

    expect($alternate->hreflang)->toBe('en-us')
        ->and($alternate->url)->toBeInstanceOf(CanonicalUrl::class)
        ->and($alternate->toArray())->toBe([
            'hreflang' => 'en-us',
            'url' => 'https://example.test/page',
        ]);
});

it('accepts the reserved x-default hreflang', function (): void {
    expect(Alternate::for('x-default', 'https://example.test/page')->hreflang)->toBe('x-default');
});

it('accepts an existing CanonicalUrl', function (): void {
    $alt = Alternate::for('de', CanonicalUrl::from('https://example.test/de'));

    expect($alt->url->toString())->toBe('https://example.test/de');
});

it('rejects invalid hreflang tags', function (string $hreflang): void {
    expect(fn () => Alternate::for($hreflang, 'https://example.test/page'))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => '',
    'symbols' => 'en;US',
    'space' => 'en US',
    'too-long' => 'this-is-not-an-hreflang-tag',
]);

it('rejects unsafe locations', function (): void {
    expect(fn () => Alternate::for('en', 'javascript:alert(1)'))
        ->toThrow(InvalidArgumentException::class);
});
