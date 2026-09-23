<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Throwable;

/**
 * An immutable sitemap URL entry.
 *
 * `loc` must be a valid, absolute http/https URL (validated through
 * CanonicalUrl): unsupported schemes, credentials, fragments and control
 * characters are rejected and the host is normalized. `lastmod` is stored as a
 * native date and rendered as an ISO-8601 timestamp, `changefreq` is restricted
 * to the sitemap vocabulary and `priority` must lie within 0.0-1.0. Because the
 * entry validates its own invariants, the renderer can trust the values.
 */
final readonly class SitemapEntry implements Stringable
{
    public const array CHANGEFREQ_VALUES = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    /**
     * ISO-8601 timestamp with time zone offset, e.g. 2026-09-21T10:30:00+00:00.
     */
    private const string LASTMOD_FORMAT = 'Y-m-d\TH:i:sP';

    public string $loc;

    public ?DateTimeInterface $lastmod;

    public ?string $changefreq;

    public ?float $priority;

    /**
     * @param  float|int|string|null  $priority  a 0.0-1.0 value or numeric string
     */
    public function __construct(
        string $loc,
        string|DateTimeInterface|null $lastmod = null,
        ?string $changefreq = null,
        float|int|string|null $priority = null,
    ) {
        $this->loc = CanonicalUrl::from($loc)->toString();

        if ($lastmod !== null && ! $lastmod instanceof DateTimeInterface) {
            $lastmod = $this->parseLastmod($lastmod);
        }

        $this->lastmod = $lastmod;

        if ($changefreq !== null) {
            $changefreq = strtolower(trim($changefreq));

            if (! in_array($changefreq, self::CHANGEFREQ_VALUES, true)) {
                throw new InvalidArgumentException(sprintf('The value [%s] is not a valid sitemap changefreq.', $changefreq));
            }
        }

        $this->changefreq = $changefreq;

        if ($priority !== null) {
            $priority = (float) $priority;

            if ($priority < 0.0 || $priority > 1.0) {
                throw new InvalidArgumentException('A sitemap priority must be between 0.0 and 1.0.');
            }
        }

        $this->priority = $priority;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $loc = isset($data['loc']) ? (string) $data['loc'] : '';

        if ($loc === '') {
            throw new InvalidArgumentException('A sitemap entry must include a non-empty loc.');
        }

        return new self(
            loc: $loc,
            lastmod: $data['lastmod'] ?? null,
            changefreq: $data['changefreq'] ?? null,
            priority: $data['priority'] ?? null,
        );
    }

    /**
     * Render the entry for the sitemap renderer. Absent optional fields are
     * omitted so the renderer never emits empty elements.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_merge(
            ['loc' => $this->loc],
            $this->lastmod instanceof DateTimeInterface ? ['lastmod' => $this->lastmod->format(self::LASTMOD_FORMAT)] : [],
            $this->changefreq !== null ? ['changefreq' => $this->changefreq] : [],
            $this->priority !== null ? ['priority' => (string) $this->priority] : [],
        );
    }

    public function __toString(): string
    {
        return $this->loc;
    }

    private function parseLastmod(string $value): DateTimeInterface
    {
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(sprintf('The value [%s] is not a valid lastmod date.', $value), 0, $exception);
        }
    }
}
