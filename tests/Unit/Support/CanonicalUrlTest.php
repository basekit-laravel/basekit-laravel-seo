<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;

it('normalizes scheme, host and strips the default port', function (): void {
    $canonical = CanonicalUrl::from('HTTPS://WWW.Example.COM:443/path');

    expect($canonical->toString())->toBe('https://www.example.com/path');
});

it('preserves non-default ports and frames the root path', function (): void {
    expect(CanonicalUrl::from('http://example.test:8080/a/b')->toString())
        ->toBe('http://example.test:8080/a/b')
        ->and(CanonicalUrl::from('https://example.test')->toString())
        ->toBe('https://example.test/');
});

it('preserves the query string as provided', function (): void {
    expect(CanonicalUrl::from('https://example.test/page?utm_source=foo&q=1')->toString())
        ->toBe('https://example.test/page?utm_source=foo&q=1');
});

it('is stringable', function (): void {
    $canonical = CanonicalUrl::from('https://example.test/page');

    expect((string) $canonical)->toBe('https://example.test/page')
        ->and((string) $canonical)->toBe($canonical->toString());
});

it('rejects unsupported URL schemes', function (string $url): void {
    expect(fn () => CanonicalUrl::from($url))->toThrow(InvalidArgumentException::class);
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html,<script>alert(1)</script>',
    'ftp' => 'ftp://example.test/file',
    'relative' => '/path-only',
    'protocol-relative' => '//example.test/path',
]);

it('rejects credentials, fragments and control characters', function (string $url): void {
    expect(fn () => CanonicalUrl::from($url))->toThrow(InvalidArgumentException::class);
})->with([
    'credentials' => 'https://user:pass@example.test/page',
    'user-only' => 'https://user@example.test/page',
    'fragment' => 'https://example.test/page#section',
    'nul-byte' => "https://example.test/page\x00evil",
    'line-feed' => "https://example.test/\nDisallow: /admin",
    'newline-loc' => "https://example.test/\r\n</loc>",
]);

it('rejects empty and blank values', function (): void {
    expect(fn () => CanonicalUrl::from(''))->toThrow(InvalidArgumentException::class)
        ->and(fn () => CanonicalUrl::from('   '))->toThrow(InvalidArgumentException::class);
});

it('tryFrom returns null instead of throwing for unsafe values', function (): void {
    expect(CanonicalUrl::tryFrom('javascript:alert(1)'))->toBeNull()
        ->and(CanonicalUrl::tryFrom('https://example.test/ok')->toString())
        ->toBe('https://example.test/ok');
});
