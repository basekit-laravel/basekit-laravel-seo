<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSeo\Services\SitemapChunker;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapPaths;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapRenderer;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

function chunker_renderer(): SitemapRenderer
{
    return new SitemapRenderer(new SitemapPaths('/sitemap.xml'));
}

/**
 * @param  list<SitemapEntry>  $entries
 * @return array<int, array<int, SitemapEntry>>
 */
function chunk_slices(SitemapChunker $chunker, array $entries): array
{
    $slices = [];

    $chunker->chunk($entries, static function (int $index, array $slice) use (&$slices): void {
        $slices[$index] = $slice;
    });

    ksort($slices);

    return $slices;
}

/**
 * Chunk sizes for a sequential dataset, so boundary conditions read clearly.
 *
 * @return array<int, int> map of document index => entry count
 */
function chunk_sizes(int $total, int $maxUrls): array
{
    $chunker = new SitemapChunker(chunker_renderer(), $maxUrls, PHP_INT_MAX);

    $sizes = [];

    $entries = array_map(static fn (int $i): SitemapEntry => new SitemapEntry('https://example.test/pages/'.$i), range(1, $total));

    foreach (chunk_slices($chunker, $entries) as $index => $slice) {
        $sizes[$index] = count($slice);
    }

    return $sizes;
}

it('produces a single empty document for an empty site', function (): void {
    $chunker = new SitemapChunker(chunker_renderer(), 50_000, 50 * 1024 * 1024);

    $slices = chunk_slices($chunker, []);

    expect($slices)->toBe([1 => []])
        ->and($chunker->chunk([], static fn (): null => null)->count())->toBe(1);
});

it('splits by URL count at the protocol boundaries', function (): void {
    expect(chunk_sizes(1, 4))->toBe([1 => 1])
        ->and(chunk_sizes(3, 4))->toBe([1 => 3])
        ->and(chunk_sizes(4, 4))->toBe([1 => 4])
        ->and(chunk_sizes(5, 4))->toBe([1 => 4, 2 => 1])
        ->and(chunk_sizes(8, 4))->toBe([1 => 4, 2 => 4])
        ->and(chunk_sizes(9, 4))->toBe([1 => 4, 2 => 4, 3 => 1]);
});

it('splits by byte size when the rendered document would grow too large', function (): void {
    $renderer = chunker_renderer();

    $entries = [
        new SitemapEntry(loc: 'https://example.test/pages/1'),
        new SitemapEntry(loc: 'https://example.test/pages/2'),
        new SitemapEntry(loc: 'https://example.test/pages/3'),
    ];

    $perEntry = $renderer->entryBytes($entries[0]);
    $maxBytes = $renderer->scaffoldBytes() + 2 * $perEntry;

    $chunker = new SitemapChunker($renderer, 50_000, $maxBytes);

    expect(chunk_slices($chunker, $entries))->toBe([
        1 => [$entries[0], $entries[1]],
        2 => [$entries[2]],
    ]);
});

it('fits entries at the exact byte boundary and splits beyond it', function (): void {
    $renderer = chunker_renderer();

    $a = new SitemapEntry(loc: 'https://example.test/a');
    $b = new SitemapEntry(loc: 'https://example.test/b');
    $c = new SitemapEntry(loc: 'https://example.test/c');

    $exactFit = $renderer->scaffoldBytes() + $renderer->entryBytes($a) + $renderer->entryBytes($b) + $renderer->entryBytes($c);
    $fitsTwo = $renderer->scaffoldBytes() + $renderer->entryBytes($a) + $renderer->entryBytes($b);

    expect(new SitemapChunker($renderer, 50_000, $exactFit)->chunk([$a, $b, $c], static fn (): null => null)->count())->toBe(1)
        ->and(new SitemapChunker($renderer, 50_000, $fitsTwo)->chunk([$a, $b, $c], static fn (): null => null)->count())->toBe(2);
});

it('splits multibyte locations by their byte length, not character count', function (): void {
    $renderer = chunker_renderer();

    $asciiA = new SitemapEntry(loc: 'https://example.test/abc');
    $asciiB = new SitemapEntry(loc: 'https://example.test/def');
    $heavy = new SitemapEntry(loc: 'https://example.test/äöü');

    $budget = $renderer->scaffoldBytes() + $renderer->entryBytes($asciiA) + $renderer->entryBytes($asciiB);

    expect(new SitemapChunker($renderer, 50_000, $budget)->chunk([$asciiA, $asciiB], static fn (): null => null)->count())->toBe(1)
        ->and($renderer->entryBytes($heavy))->toBe($renderer->entryBytes($asciiB) + 3)
        ->and(new SitemapChunker($renderer, 50_000, $budget)->chunk([$asciiA, $heavy], static fn (): null => null)->count())->toBe(2);
});

it('records byte sizes that match the actual rendered chunk bytes', function (): void {
    $renderer = chunker_renderer();

    $entries = [
        new SitemapEntry(loc: 'https://example.test/éclair'),
        new SitemapEntry(loc: 'https://example.test/pages/2'),
        new SitemapEntry(loc: 'https://example.test/pages/3'),
    ];

    $maxBytes = $renderer->scaffoldBytes() + 2 * $renderer->entryBytes($entries[0]) + 3;

    $slices = chunk_slices(new SitemapChunker($renderer, 50_000, $maxBytes), $entries);
    $catalog = new SitemapChunker($renderer, 50_000, $maxBytes)->chunk($entries, static fn (): null => null);

    foreach ($catalog->documents() as $document) {
        expect($document->bytes)->toBe(strlen($renderer->urlset($slices[$document->index])))
            ->and($document->bytes)->toBeLessThanOrEqual($maxBytes);
    }
});

it('throws a meaningful exception when a single entry exceeds the document size', function (): void {
    $renderer = chunker_renderer();

    $maxBytes = $renderer->scaffoldBytes();

    $oversized = new SitemapEntry(loc: 'https://example.test/'.str_repeat('a', 1000));

    expect(fn (): SitemapCatalog => new SitemapChunker($renderer, 50_000, $maxBytes)->chunk([$oversized], static fn (): null => null))
        ->toThrow(InvalidArgumentException::class, 'exceeds the configured maximum sitemap document size');
});

it('rejects invalid limits', function (): void {
    $renderer = chunker_renderer();

    expect(fn (): SitemapChunker => new SitemapChunker($renderer, 0, 50 * 1024 * 1024))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): SitemapChunker => new SitemapChunker($renderer, 1, $renderer->scaffoldBytes() - 1))
        ->toThrow(InvalidArgumentException::class);
});

it('exposes document lookup and index detection on the catalog', function (): void {
    $renderer = chunker_renderer();

    $entries = array_map(static fn (int $i): SitemapEntry => new SitemapEntry('https://example.test/pages/'.$i), range(1, 5));

    $catalog = new SitemapChunker($renderer, 2, PHP_INT_MAX)->chunk($entries, static fn (): null => null);

    expect($catalog->isIndex())->toBeTrue()
        ->and($catalog->count())->toBe(3)
        ->and($catalog->totalEntries())->toBe(5)
        ->and($catalog->document(1)?->count)->toBe(2)
        ->and($catalog->document(3)?->count)->toBe(1)
        ->and($catalog->document(99))->toBeNull();
});

it('round-trips the catalog and its documents through arrays', function (): void {
    $renderer = chunker_renderer();

    $entries = array_map(static fn (int $i): SitemapEntry => new SitemapEntry('https://example.test/pages/'.$i), range(1, 3));

    $catalog = new SitemapChunker($renderer, 2, PHP_INT_MAX)->chunk($entries, static fn (): null => null);

    $restored = SitemapCatalog::fromArray($catalog->toArray());

    expect($restored->toArray())->toBe($catalog->toArray())
        ->and($restored->count())->toBe($catalog->count());
});

it('preserves entry order within chunks and across documents', function (): void {
    $renderer = chunker_renderer();

    $entries = array_map(static fn (int $i): SitemapEntry => new SitemapEntry('https://example.test/pages/'.$i), range(1, 5));

    $slices = chunk_slices(new SitemapChunker($renderer, 2, PHP_INT_MAX), $entries);

    $order = array_merge(...$slices);

    expect(array_map(static fn (SitemapEntry $entry): string => $entry->loc, $order))
        ->toBe(['https://example.test/pages/1', 'https://example.test/pages/2', 'https://example.test/pages/3', 'https://example.test/pages/4', 'https://example.test/pages/5']);
});

it('keeps document indices correct when a run is re-entered', function (): void {
    // The chunker is shared as a container singleton, so an instance that kept
    // per-run state on $this would interleave two concurrent runs: the inner
    // run's documents would advance the shared counter and the outer run would
    // be numbered 1, 4, 5 instead of 1, 2, 3.
    $chunker = new SitemapChunker(chunker_renderer(), 2, PHP_INT_MAX);

    $entry = static fn (string $slug): SitemapEntry => new SitemapEntry('https://example.test/'.$slug);

    $outer = [];
    $inner = [];

    $chunker->chunk(
        [$entry('a'), $entry('b'), $entry('c'), $entry('d'), $entry('e')],
        static function (int $index, array $slice) use (&$outer, $chunker, $entry, &$inner): void {
            $outer[$index] = array_map(static fn (SitemapEntry $e): string => $e->loc, $slice);

            if ($index === 1) {
                $chunker->chunk(
                    [$entry('x'), $entry('y'), $entry('z')],
                    static function (int $i, array $s) use (&$inner): void {
                        $inner[$i] = count($s);
                    },
                );
            }
        },
    );

    expect($outer)->toBe([
        1 => ['https://example.test/a', 'https://example.test/b'],
        2 => ['https://example.test/c', 'https://example.test/d'],
        3 => ['https://example.test/e'],
    ])->and($inner)->toBe([1 => 2, 2 => 1]);
});

it('releases buffered entries when a provider fails mid-stream', function (): void {
    // An aborted run used to leave its buffer on the singleton, retaining up to
    // max_urls entries for the lifetime of a long-running worker.
    $chunker = new SitemapChunker(chunker_renderer(), 50_000, 50 * 1024 * 1024);

    $stream = (static function (): Generator {
        for ($i = 0; $i < 5_000; $i++) {
            yield new SitemapEntry('https://example.test/pages/'.$i);
        }

        throw new RuntimeException('provider exploded');
    })();

    expect(fn (): SitemapCatalog => $chunker->chunk($stream, static fn (): null => null))
        ->toThrow(RuntimeException::class, 'provider exploded');

    $mutable = array_map(
        static fn (ReflectionProperty $p): string => $p->getName(),
        array_filter(
            (new ReflectionClass($chunker))->getProperties(),
            static fn (ReflectionProperty $p): bool => ! $p->isReadOnly(),
        ),
    );

    expect($mutable)->toBe([]);
});
