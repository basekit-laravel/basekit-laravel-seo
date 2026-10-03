<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;
use BasekitLaravel\BasekitLaravelSeo\Support\TwitterMeta;
use BasekitLaravel\BasekitLaravelSeo\Support\WebPageSchema;

it('escapes Open Graph values so they cannot break out of the meta attribute', function (): void {
    $og = OpenGraph::make()->withTitle('" onload="alert(1)');

    expect((string) $og)
        ->not->toContain('" onload="alert(1)')
        ->toContain('&quot; onload=&quot;alert(1)');
});

it('escapes Twitter card values so they cannot break out of the meta attribute', function (): void {
    $twitter = TwitterMeta::make()->withTitle('" onload="alert(1)');

    expect((string) $twitter)
        ->not->toContain('" onload="alert(1)')
        ->toContain('&quot; onload=&quot;alert(1)');
});

it('substitutes invalid UTF-8 in JSON-LD instead of throwing', function (): void {
    $schema = WebPageSchema::make()->name("Bad \xFF name");

    expect($schema->toJson())
        ->toContain("\u{FFFD}")
        // The emitted JSON-LD must still be valid JSON.
        ->and(json_decode($schema->toJson(), true))->not->toBeNull()
        ->and($schema->render())->toStartWith('<script type="application/ld+json">');
});
