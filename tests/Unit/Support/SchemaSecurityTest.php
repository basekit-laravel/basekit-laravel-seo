<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\OrganizationSchema;

it('escapes HTML-breaking payloads inside JSON-LD', function (): void {
    $html = OrganizationSchema::make()
        ->name('Acme')
        ->description('</script><script>alert(1)</script>')
        ->render();

    expect($html)->not->toContain('</script><script>')
        ->and(decode_ld_json($html)['description'])->toBe('</script><script>alert(1)</script>');
});

it('escapes tag-like and ampersand payloads without breaking JSON', function (): void {
    $payload = '<b>Acme</b> & Partners "quoted" \'single\'';
    $html = OrganizationSchema::make()->name($payload)->render();

    $decoded = decode_ld_json($html);

    expect($decoded['name'])->toBe($payload)
        ->and($html)->not->toContain('<b>Acme</b>');
});

it('still renders valid, parseable JSON for every schema', function (): void {
    $html = OrganizationSchema::make()
        ->name('Acme')
        ->description("Description with <script> and \u{2028} line separators.")
        ->render();

    expect(static fn (): mixed => json_decode(
        (string) preg_replace('/^<script type="application\/ld\+json">|<\/script>$/', '', $html),
        true,
        512,
        JSON_THROW_ON_ERROR,
    ))->not->toThrow(JsonException::class);
});

it('round-trips JSON-LD through the escape helpers without corruption', function (): void {
    $payload = 'Café ✓ </script><b>&amp;</b>';
    $html = OrganizationSchema::make()->description($payload)->render();

    expect(decode_ld_json($html)['description'])->toBe($payload);
});
