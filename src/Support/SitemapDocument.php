<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use InvalidArgumentException;

/**
 * Metadata for a single generated sitemap document (one rendered urlset).
 *
 * This is not an entry (a URL) and not the whole index: it describes one
 * chunk of the aggregate, so the cache and the sitemap index know how many
 * documents exist, how many URLs each holds and how large the rendered XML is.
 */
final readonly class SitemapDocument
{
    public int $index;

    public int $count;

    public int $bytes;

    public function __construct(int $index, int $count, int $bytes)
    {
        if ($index < 1) {
            throw new InvalidArgumentException('A sitemap document index must be at least 1.');
        }

        if ($count < 0) {
            throw new InvalidArgumentException('A sitemap document cannot have a negative entry count.');
        }

        if ($bytes < 0) {
            throw new InvalidArgumentException('A sitemap document cannot have a negative byte size.');
        }

        $this->index = $index;
        $this->count = $count;
        $this->bytes = $bytes;
    }

    /**
     * @return array{index: int, count: int, bytes: int}
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'count' => $this->count,
            'bytes' => $this->bytes,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $index = $data['index'] ?? null;
        $count = $data['count'] ?? null;
        $bytes = $data['bytes'] ?? null;

        return new self(
            index: self::requireInt($index, 'index'),
            count: self::requireInt($count, 'count'),
            bytes: self::requireInt($bytes, 'bytes'),
        );
    }

    private static function requireInt(mixed $value, string $name): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException(sprintf('A sitemap document %s must be an integer.', $name));
        }

        return $value;
    }
}
