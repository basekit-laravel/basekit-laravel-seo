<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use InvalidArgumentException;
use Stringable;

/**
 * A normalized, absolute canonical URL.
 *
 * The value object only accepts http/https URLs, rejects credentials,
 * fragments, control characters and non-hosts, lowercases the host and strips
 * the default port. The query string is preserved as provided.
 */
final readonly class CanonicalUrl implements Stringable
{
    private function __construct(public string $url) {}

    /**
     * Validate and build a canonical URL from a raw string.
     *
     * @throws InvalidArgumentException when the value is not a safe absolute URL
     */
    public static function from(string $url): self
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            throw new InvalidArgumentException('A canonical URL must not be empty.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed) === 1) {
            throw new InvalidArgumentException('A canonical URL must not contain control characters.');
        }

        $parts = parse_url($trimmed);

        if (! is_array($parts)) {
            throw new InvalidArgumentException(sprintf('The value [%s] is not a valid URL.', $trimmed));
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(sprintf('The value [%s] is not a supported URL scheme.', $trimmed));
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('A canonical URL must not contain user credentials.');
        }

        if (isset($parts['fragment'])) {
            throw new InvalidArgumentException('A canonical URL must not contain a fragment.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            throw new InvalidArgumentException(sprintf('The value [%s] does not contain a host.', $trimmed));
        }

        $port = $parts['port'] ?? null;

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $path = (string) ($parts['path'] ?? '');

        if ($path === '') {
            $path = '/';
        }

        $normalized = $scheme.'://'.$host.($port !== null ? ':'.$port : '').$path;

        if (isset($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return new self($normalized);
    }

    /**
     * Try to validate a canonical URL, returning null when it is unsafe.
     */
    public static function tryFrom(string $url): ?self
    {
        try {
            return self::from($url);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function toString(): string
    {
        return $this->url;
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
