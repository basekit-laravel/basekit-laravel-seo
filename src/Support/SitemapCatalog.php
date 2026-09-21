<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use InvalidArgumentException;

/**
 * The result of splitting an aggregated sitemap into documents.
 *
 * A catalog of zero/one documents means the site fits in a single urlset;
 * two or more documents mean `/sitemap.xml` must become a sitemap index. The
 * catalog itself is origin- and request-agnostic — index URLs are resolved
 * against the trusted canonical origin at render time only.
 */
final readonly class SitemapCatalog
{
    /**
     * @var list<SitemapDocument>
     */
    public array $documents;

    /**
     * @param  iterable<SitemapDocument>  $documents
     */
    public function __construct(iterable $documents)
    {
        $list = [];

        foreach ($documents as $document) {
            $list[] = $document;
        }

        $this->documents = $list;
    }

    /**
     * @return list<SitemapDocument>
     */
    public function documents(): array
    {
        return $this->documents;
    }

    /**
     * The number of documents the aggregate was split into.
     */
    public function count(): int
    {
        return count($this->documents);
    }

    /**
     * Whether the site is large enough that a sitemap index is required.
     */
    public function isIndex(): bool
    {
        return $this->count() > 1;
    }

    /**
     * The total number of entries across all documents.
     */
    public function totalEntries(): int
    {
        $total = 0;

        foreach ($this->documents as $document) {
            $total += $document->count;
        }

        return $total;
    }

    public function document(int $index): ?SitemapDocument
    {
        foreach ($this->documents as $document) {
            if ($document->index === $index) {
                return $document;
            }
        }

        return null;
    }

    /**
     * @return array{documents: list<array{index: int, count: int, bytes: int}>}
     */
    public function toArray(): array
    {
        return [
            'documents' => array_map(static fn (SitemapDocument $document): array => $document->toArray(), $this->documents),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['documents']) || ! is_array($data['documents'])) {
            throw new InvalidArgumentException('A sitemap catalog must include a documents list.');
        }

        $documents = array_map(
            static fn (mixed $document): SitemapDocument => SitemapDocument::fromArray(is_array($document) ? $document : []),
            $data['documents'],
        );

        return new self($documents);
    }
}
