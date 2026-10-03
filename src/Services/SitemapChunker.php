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
 *
 * The class is deliberately stateless: all per-run bookkeeping lives in locals
 * inside `chunk()`, so a single instance can safely serve concurrent or
 * re-entrant calls. That matters because the class is shared as a container
 * singleton, and long-running workers (Octane) keep that instance alive across
 * requests — instance state would otherwise leak between requests and an
 * aborted run would retain a whole document's worth of entries.
 */
final class SitemapChunker
{
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
        $scaffold = $this->renderer->scaffoldBytes();

        $documents = [];
        $buffer = [];
        $count = 0;
        $bytes = $scaffold;

        foreach ($entries as $entry) {
            $fragmentBytes = $this->renderer->entryBytes($entry);

            if ($count > 0 && ($count + 1 > $this->maxUrls || $bytes + $fragmentBytes > $this->maxBytes)) {
                $this->flush($onChunk, $documents, $buffer, $count, $bytes, $scaffold);
            }

            if ($bytes + $fragmentBytes > $this->maxBytes) {
                throw new InvalidArgumentException(sprintf(
                    'The sitemap entry [%s] serializes to %d bytes, which exceeds the configured maximum sitemap document size (%d bytes).',
                    $entry->loc,
                    $fragmentBytes,
                    $this->maxBytes,
                ));
            }

            $buffer[] = $entry;
            $count++;
            $bytes += $fragmentBytes;
        }

        $this->flush($onChunk, $documents, $buffer, $count, $bytes, $scaffold);

        if ($documents === []) {
            $onChunk(1, []);

            $documents[] = new SitemapDocument(
                index: 1,
                count: 0,
                bytes: $scaffold,
            );
        }

        return new SitemapCatalog($documents);
    }

    /**
     * Finalize the buffered entries as a document and reset the run state.
     *
     * Every argument is a local variable of `chunk()`, passed by reference so
     * this method stays allocation-free while keeping the state off `$this`.
     *
     * @param  list<SitemapDocument>  $documents
     * @param  list<SitemapEntry>  $buffer
     * @param  callable(int, list<SitemapEntry>): void  $onChunk
     */
    private function flush(
        callable $onChunk,
        array &$documents,
        array &$buffer,
        int &$count,
        int &$bytes,
        int $scaffold,
    ): void {
        if ($count === 0) {
            return;
        }

        $index = count($documents) + 1;

        $onChunk($index, $buffer);

        $documents[] = new SitemapDocument(index: $index, count: $count, bytes: $bytes);

        $buffer = [];
        $count = 0;
        $bytes = $scaffold;
    }
}
