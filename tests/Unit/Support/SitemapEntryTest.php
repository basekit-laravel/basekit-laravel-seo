<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

it('accepts an http location and normalizes it', function (): void {
    $entry = new SitemapEntry(loc: 'http://example.test/about');

    expect($entry->loc)->toBe('http://example.test/about')
        ->and($entry->toArray()['loc'])->toBe('http://example.test/about');
});

it('accepts an https location and preserves its path and query', function (): void {
    $entry = new SitemapEntry(loc: 'https://example.test/products?page=2');

    expect($entry->loc)->toBe('https://example.test/products?page=2');
});

it('normalizes the host to lowercase and strips the default port', function (): void {
    $entry = new SitemapEntry(loc: 'https://EXAMPLE.test:443/About');

    expect($entry->loc)->toBe('https://example.test/About');
});

it('rejects unsupported URL schemes', function (string $loc): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: $loc))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'javascript:' => 'javascript:alert(1)',
    'data:' => 'data:text/html,<script>alert(1)</script>',
    'file:' => 'file:///etc/passwd',
    'ftp:' => 'ftp://example.test/file.txt',
    'no scheme' => 'example.test/path',
]);

it('rejects empty and malformed locations', function (string $loc): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: $loc))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => '',
    'whitespace' => '   ',
    'missing host' => 'https:///path',
    'no host' => 'not a url',
]);

it('rejects control characters and line breaks in the location', function (string $loc): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: $loc))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'null byte' => "https://example.test/\x00page",
    'crlf' => "https://example.test/path\r\nSitemap: https://evil.test",
    'vertical tab' => "https://example.test/\x0Bpage",
    'delete' => "https://example.test/\x7Fpage",
]);

it('rejects fragments and credentials in the location', function (string $loc): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: $loc))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'fragment' => 'https://example.test/page#section',
    'username' => 'https://user@example.test/page',
    'credentials' => 'https://user:pass@example.test/page',
]);

it('accepts and normalizes a valid lastmod string', function (): void {
    $entry = new SitemapEntry(loc: 'https://example.test/', lastmod: '2026-09-21T10:30:00+00:00');

    expect($entry->lastmod)->toBeInstanceOf(DateTimeInterface::class)
        ->and($entry->toArray()['lastmod'])->toBe('2026-09-21T10:30:00+00:00');
});

it('normalizes a lastmod to the documented ISO-8601 form', function (): void {
    $entry = new SitemapEntry(loc: 'https://example.test/', lastmod: '2026-09-21T10:30:00+02:00');

    expect($entry->toArray()['lastmod'])->toBe('2026-09-21T10:30:00+02:00');
});

it('accepts a DateTimeInterface lastmod', function (): void {
    $date = new DateTimeImmutable('2026-09-21T10:30:00+00:00');

    $entry = new SitemapEntry(loc: 'https://example.test/', lastmod: $date);

    expect($entry->lastmod)->toBe($date)
        ->and($entry->toArray()['lastmod'])->toBe('2026-09-21T10:30:00+00:00');
});

it('rejects an unparseable lastmod', function (): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: 'https://example.test/', lastmod: 'not-a-date'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts every standard changefreq value case-insensitively', function (string $raw, string $normalized): void {
    $entry = new SitemapEntry(loc: 'https://example.test/', changefreq: $raw);

    expect($entry->changefreq)->toBe($normalized)
        ->and($entry->toArray()['changefreq'])->toBe($normalized);
})->with([
    'always' => ['ALWAYS', 'always'],
    'hourly' => ['Hourly', 'hourly'],
    'daily' => ['daily', 'daily'],
    'weekly' => ['weekly', 'weekly'],
    'monthly' => ['MONTHLY', 'monthly'],
    'yearly' => ['yearly', 'yearly'],
    'never' => ['never', 'never'],
]);

it('rejects an unknown changefreq', function (): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: 'https://example.test/', changefreq: 'sometimes'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts priorities within 0.0 and 1.0', function (mixed $raw, string $rendered): void {
    $entry = new SitemapEntry(loc: 'https://example.test/', priority: $raw);

    expect($entry->priority)->toBe((float) $raw)
        ->and($entry->toArray()['priority'])->toBe($rendered);
})->with([
    'zero' => [0.0, '0'],
    'half' => [0.5, '0.5'],
    'one' => [1.0, '1'],
    'string' => ['0.8', '0.8'],
    'integer' => [1, '1'],
]);

it('rejects priorities outside 0.0 and 1.0', function (mixed $priority): void {
    expect(fn (): SitemapEntry => new SitemapEntry(loc: 'https://example.test/', priority: $priority))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'negative' => -0.1,
    'above one' => 1.1,
    'string negative' => '-0.5',
    'string above one' => '2.0',
]);

it('omits optional fields from the array form when absent', function (): void {
    $entry = new SitemapEntry(loc: 'https://example.test/', lastmod: '2026-09-21T10:30:00+00:00');

    expect($entry->toArray())->toBe([
        'loc' => 'https://example.test/',
        'lastmod' => '2026-09-21T10:30:00+00:00',
    ]);
});

it('round-trips through the array form', function (): void {
    $entry = new SitemapEntry(
        loc: 'https://example.test/about',
        lastmod: '2026-09-21T10:30:00+00:00',
        changefreq: 'monthly',
        priority: '0.8',
    );

    expect(SitemapEntry::fromArray($entry->toArray())->toArray())->toBe($entry->toArray());
});

it('is an immutable value object', function (): void {
    $class = new ReflectionClass(SitemapEntry::class);

    expect($class->isFinal())->toBeTrue()
        ->and($class->isReadOnly())->toBeTrue()
        ->and((string) new SitemapEntry('https://example.test/a'))->toBe('https://example.test/a');
});

it('has value semantics independent of instances', function (): void {
    $a = new SitemapEntry('https://example.test/a', priority: 0.8);
    $b = new SitemapEntry('https://example.test/a', priority: 0.8);

    expect($a->loc)->toBe($b->loc)
        ->and($a->toArray())->toBe($b->toArray());
});
