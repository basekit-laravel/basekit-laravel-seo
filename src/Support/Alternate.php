<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use InvalidArgumentException;

/**
 * An hreflang alternate for a page.
 *
 * The hreflang is BCP-47-ish (lowercased language tag, optionally followed by
 * region) or the reserved `x-default` value. The URL follows the same safety
 * rules as canonical URLs.
 */
final readonly class Alternate
{
    public function __construct(
        public string $hreflang,
        public CanonicalUrl $url,
    ) {}

    /**
     * Build an alternate for a language and location.
     */
    public static function for(string $hreflang, string|CanonicalUrl $url): self
    {
        $hreflang = strtolower(trim($hreflang));

        if ($hreflang !== 'x-default' && preg_match('/^[a-z]{2,3}(-[a-z0-9]{1,8})*$/', $hreflang) !== 1) {
            throw new InvalidArgumentException(sprintf('The value [%s] is not a valid hreflang tag.', $hreflang));
        }

        if ($url instanceof CanonicalUrl) {
            return new self($hreflang, $url);
        }

        return new self($hreflang, CanonicalUrl::from($url));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::for(
            isset($data['hreflang']) ? (string) $data['hreflang'] : '',
            isset($data['url']) ? (string) $data['url'] : '',
        );
    }

    /**
     * @return array{hreflang: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'hreflang' => $this->hreflang,
            'url' => $this->url->toString(),
        ];
    }
}
