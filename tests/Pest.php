<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Extract and decode the first JSON-LD object from a rendered
 * application/ld+json script block.
 *
 * @return array<string, mixed>
 */
function decode_ld_json(string $html): array
{
    if (! preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
        throw new RuntimeException('No JSON-LD script block found.');
    }

    $decoded = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

    if (! is_array($decoded)) {
        throw new RuntimeException('JSON-LD did not decode to an array.');
    }

    return $decoded;
}
