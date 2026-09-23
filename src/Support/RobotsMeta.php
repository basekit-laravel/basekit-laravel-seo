<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Stringable;

/**
 * Page-level robots directives rendered in an HTML `<meta name="robots">` tag.
 *
 * Directives are trimmed, lowercased and deduplicated so output is predictable
 * regardless of how they are provided (array, comma-separated string or both).
 *
 * @phpstan-consistent-constructor
 */
final readonly class RobotsMeta implements Stringable
{
    public const string INDEX = 'index';

    public const string NOINDEX = 'noindex';

    public const string FOLLOW = 'follow';

    public const string NOFOLLOW = 'nofollow';

    public const string NOARCHIVE = 'noarchive';

    public const string NOSNIPPET = 'nosnippet';

    public const string NOIMAGEINDEX = 'noimageindex';

    /**
     * @var list<string>
     */
    public array $directives;

    /**
     * @param  array<int, string>  $directives
     */
    public function __construct(array $directives = [])
    {
        $this->directives = $this->normalize($directives);
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  string|array<int, string>|null  $directives
     */
    public static function from(string|array|null $directives = null): self
    {
        if ($directives === null) {
            return new self;
        }

        if (is_string($directives)) {
            return new self(explode(',', $directives));
        }

        return new self($directives);
    }

    public function withDirective(string $directive): self
    {
        return new self([...$this->directives, $directive]);
    }

    public function without(string $directive): self
    {
        $normalized = strtolower(trim($directive));

        return new self(array_values(array_filter(
            $this->directives,
            static fn (string $item): bool => $item !== $normalized,
        )));
    }

    public function has(string $directive): bool
    {
        return in_array(strtolower(trim($directive)), $this->directives, true);
    }

    public function isNoindex(): bool
    {
        return $this->has(self::NOINDEX);
    }

    public function isNofollow(): bool
    {
        return $this->has(self::NOFOLLOW);
    }

    public function toString(): string
    {
        return implode(', ', $this->directives);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->directives;
    }

    /**
     * @param  array<int, string>  $directives
     * @return list<string>
     */
    private function normalize(array $directives): array
    {
        $result = [];

        foreach ($directives as $directive) {
            $normalized = strtolower(trim((string) $directive));

            if ($normalized === '') {
                continue;
            }

            $result[] = $normalized;
        }

        return array_values(array_unique($result));
    }
}
