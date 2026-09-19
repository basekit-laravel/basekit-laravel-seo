<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

/**
 * Abstract base for structured-data (JSON-LD) builders.
 *
 * A schema value object exposes a plain associative array via `toArray()` and
 * renders itself as a JSON-LD <script type="application/ld+json"> block through
 * `render()`. Values are JSON-encoded with unescaped slashes and forced to a
 * JSON object (never a bare array) so the output is always valid schema.org.
 *
 * @phpstan-consistent-constructor
 */
abstract class Schema
{
    /**
     * The JSON-LD @type for this schema.
     */
    protected string $type = 'Thing';

    /**
     * A fresh schema instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * The schema graph as an associative array, keyed by @context/@type.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $this->type,
            ...$this->attributes(),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * The schema's data attributes (excludes @context/@type).
     *
     * @return array<string, mixed>
     */
    abstract protected function attributes(): array;

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function toJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                | JSON_THROW_ON_ERROR,
        );
    }

    public function render(): string
    {
        return '<script type="application/ld+json">'.$this->toJson().'</script>';
    }

    /**
     * Convert an array of Schema objects to nested array data.
     *
     * @param  iterable<Schema|array<mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function mapNested(iterable $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = $item instanceof self ? $item->toArray() : $item;
        }

        return $result;
    }
}
