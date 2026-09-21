<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapDocument;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;
use InvalidArgumentException;

/**
 * Splits an aggregated sitemap stream into documents at entry boundaries.
 *
 * A document is finalized — and a new one started — when adding the next entry
 * would exceed either the configured URL count or the configured byte size. The
 * byte accounting uses the renderer's exact serialized sizes (UTF-8 byte
 * length, including the XML declaration and the urlset wrapper), so a rendered
 * document is never larger than `max_bytes`. Splitting never cuts inside an
 * entry; an individual entry that cannot fit in a document on its own raises a
 * meaningful exception instead of producing an unusable chunk.
 */
final class SitemapChunker
{
    /** @var list<SitemapDocument> */
    private array $documents = [];

    /** @var list<SitemapEntry> */
    private array $buffer = [];

    private int $count = 0;

    private int $bytes = 0;

    public function __construct(
        private readonly SitemapRenderer $renderer,
        private readonly int $maxUrls,
        private readonly int $maxBytes,
    ) {
        if ($this->maxUrls < 1) {
            throw new InvalidArgumentException('basekit-laravel-seo.sitemap.max_urls must be at least 1.');
        }

        if ($this->maxBytes < $this->renderer->scaffoldBytes()) {
            throw new InvalidArgumentException(sprintf(
                'basekit-laravel-seo.sitemap.max_bytes (%d) is too small to hold a sitemap document.',
                $this->maxBytes,
            ));
        }
    }

    /**
     * Iterate the entries once, invoking `$onChunk` for each finalized document
     * with its 1-based index and its entry slice, and return the catalog
     * describing the split.
     *
     * @param  iterable<SitemapEntry>  $entries
     * @param  callable(int, list<SitemapEntry>): void  $onChunk
     */
    public function chunk(iterable $entries, callable $onChunk): SitemapCatalog
    {
        $this->documents = [];
        $this->buffer = [];
        $this->count = 0;
        $this->bytes = $this->renderer->scaffoldBytes();

        foreach ($entries as $entry) {
            $fragmentBytes = $this->renderer->entryBytes($entry);

            if ($this->count > 0 && ($this->count + 1 > $this->maxUrls || $this->bytes + $fragmentBytes > $this->maxBytes)) {
                $this->flush($onChunk);
            }

            if ($this->bytes + $fragmentBytes > $this->maxBytes) {
                throw new InvalidArgumentException(sprintf(
                    'The sitemap entry [%s] serializes to %d bytes, which exceeds the configured maximum sitemap document size (%d bytes).',
                    $entry->loc,
                    $fragmentBytes,
                    $this->maxBytes,
                ));
            }

            $this->buffer[] = $entry;
            $this->count++;
            $this->bytes += $fragmentBytes;
        }

        $this->flush($onChunk);

        if ($this->documents === []) {
            $onChunk(1, []);

            $this->documents[] = new SitemapDocument(
                index: 1,
                count: 0,
                bytes: $this->renderer->scaffoldBytes(),
            );
        }

        return new SitemapCatalog($this->documents);
    }

    /**
     * @param  callable(int, list<SitemapEntry>): void  $onChunk
     */
    private function flush(callable $onChunk): void
    {
        if ($this->count === 0) {
            return;
        }

        $index = count($this->documents) + 1;

        $onChunk($index, $this->buffer);

        $this->documents[] = new SitemapDocument(index: $index, count: $this->count, bytes: $this->bytes);

        $this->buffer = [];
        $this->count = 0;
        $this->bytes = $this->renderer->scaffoldBytes();
    }
}
