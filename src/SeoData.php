<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo;

use BasekitLaravel\BasekitLaravelSeo\Support\Alternate;
use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;
use BasekitLaravel\BasekitLaravelSeo\Support\RobotsMeta;
use BasekitLaravel\BasekitLaravelSeo\Support\Schema;
use BasekitLaravel\BasekitLaravelSeo\Support\TwitterMeta;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable SEO metadata for a single page or content type.
 *
 * An instance is a snapshot: every `with*()` method returns a new instance.
 * A `null` value means "not provided" — resolution/merging happens in the
 * SeoManager, which picks the first non-null value from lower layers and
 * concatenates lists. Schemas and alternates are plain arrays via `toArray()`.
 *
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
final readonly class SeoData implements Arrayable, JsonSerializable
{
    /**
     * @param  list<Alternate>  $alternates
     * @param  list<Schema|array<string, mixed>>  $schemas
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?CanonicalUrl $canonicalUrl = null,
        public ?RobotsMeta $robots = null,
        public ?OpenGraph $openGraph = null,
        public ?TwitterMeta $twitter = null,
        public ?string $locale = null,
        public array $alternates = [],
        public array $schemas = [],
    ) {}

    public static function make(): static
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $alternates = [];

        foreach ((array) ($data['alternates'] ?? []) as $value) {
            $alternates[] = $value instanceof Alternate ? $value : Alternate::fromArray((array) $value);
        }

        $schemas = [];

        foreach ((array) ($data['schemas'] ?? []) as $value) {
            if (! is_array($value)) {
                throw new InvalidArgumentException('SeoData schemas must be arrays.');
            }

            $schemas[] = $value;
        }

        return new static(
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            canonicalUrl: isset($data['canonical_url']) ? CanonicalUrl::from((string) $data['canonical_url']) : (isset($data['canonicalUrl']) ? CanonicalUrl::from((string) $data['canonicalUrl']) : null),
            robots: isset($data['robots']) ? RobotsMeta::from($data['robots']) : null,
            openGraph: isset($data['open_graph']) ? OpenGraph::fromArray((array) $data['open_graph']) : (isset($data['openGraph']) ? OpenGraph::fromArray((array) $data['openGraph']) : null),
            twitter: isset($data['twitter']) ? TwitterMeta::fromArray((array) $data['twitter']) : null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            alternates: $alternates,
            schemas: $schemas,
        );
    }

    public function withTitle(?string $title): static
    {
        return $this->with(['title' => $title]);
    }

    public function withDescription(?string $description): static
    {
        return $this->with(['description' => $description]);
    }

    public function withCanonicalUrl(string|CanonicalUrl|null $url): static
    {
        if (is_string($url)) {
            $url = CanonicalUrl::from($url);
        }

        return $this->with(['canonicalUrl' => $url]);
    }

    /**
     * @param  array<int, string>  $robots
     */
    public function withRobots(RobotsMeta|string|array|null $robots): static
    {
        if (is_string($robots) || is_array($robots)) {
            $robots = RobotsMeta::from($robots);
        }

        return $this->with(['robots' => $robots]);
    }

    /**
     * @param  array<string, mixed>  $openGraph
     */
    public function withOpenGraph(OpenGraph|array|null $openGraph): static
    {
        if (is_array($openGraph)) {
            $openGraph = OpenGraph::fromArray($openGraph);
        }

        return $this->with(['openGraph' => $openGraph]);
    }

    /**
     * @param  array<string, mixed>  $twitter
     */
    public function withTwitter(TwitterMeta|array|null $twitter): static
    {
        if (is_array($twitter)) {
            $twitter = TwitterMeta::fromArray($twitter);
        }

        return $this->with(['twitter' => $twitter]);
    }

    public function withLocale(?string $locale): static
    {
        return $this->with(['locale' => $locale]);
    }

    public function withAlternate(string $hreflang, string|CanonicalUrl $url): static
    {
        return $this->with([
            'alternates' => [...$this->alternates, Alternate::for($hreflang, $url)],
        ]);
    }

    /**
     * @param  list<Alternate|array<string, mixed>>  $alternates
     */
    public function withAlternates(array $alternates): static
    {
        $normalized = [];

        foreach ($alternates as $alternate) {
            $normalized[] = $alternate instanceof Alternate ? $alternate : Alternate::fromArray($alternate);
        }

        return $this->with(['alternates' => $normalized]);
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function withSchema(Schema|array $schema): static
    {
        return $this->with(['schemas' => [...$this->schemas, $schema]]);
    }

    /**
     * @param  list<Schema|array<string, mixed>>  $schemas
     */
    public function withSchemas(array $schemas): static
    {
        return $this->with(['schemas' => $schemas]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'canonical_url' => $this->canonicalUrl?->toString(),
            'robots' => $this->robots?->toArray(),
            'open_graph' => $this->openGraph?->toArray(),
            'twitter' => $this->twitter?->toArray(),
            'locale' => $this->locale,
            'alternates' => array_map(
                static fn (Alternate $alternate): array => $alternate->toArray(),
                $this->alternates,
            ),
            'schemas' => array_map(
                static fn (Schema|array $schema): array => $schema instanceof Schema ? $schema->toArray() : $schema,
                $this->schemas,
            ),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Build a new instance applying only the given changes.
     *
     * @param  array<string, mixed>  $changes
     */
    private function with(array $changes): static
    {
        return new static(
            title: array_key_exists('title', $changes) ? $this->nullableString($changes['title']) : $this->title,
            description: array_key_exists('description', $changes) ? $this->nullableString($changes['description']) : $this->description,
            canonicalUrl: $this->coerceCanonicalUrl(array_key_exists('canonicalUrl', $changes) ? $changes['canonicalUrl'] : $this->canonicalUrl),
            robots: $this->coerceRobots(array_key_exists('robots', $changes) ? $changes['robots'] : $this->robots),
            openGraph: $this->coerceOpenGraph(array_key_exists('openGraph', $changes) ? $changes['openGraph'] : $this->openGraph),
            twitter: $this->coerceTwitter(array_key_exists('twitter', $changes) ? $changes['twitter'] : $this->twitter),
            locale: array_key_exists('locale', $changes) ? $this->nullableString($changes['locale']) : $this->locale,
            alternates: array_key_exists('alternates', $changes) ? array_values($changes['alternates']) : $this->alternates,
            schemas: array_key_exists('schemas', $changes) ? array_values($changes['schemas']) : $this->schemas,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function coerceCanonicalUrl(mixed $value): ?CanonicalUrl
    {
        if ($value === null || $value instanceof CanonicalUrl) {
            return $value;
        }

        if (is_string($value)) {
            return CanonicalUrl::from($value);
        }

        throw new InvalidArgumentException('A canonical URL must be a string or CanonicalUrl.');
    }

    private function coerceRobots(mixed $value): ?RobotsMeta
    {
        if ($value === null || $value instanceof RobotsMeta) {
            return $value;
        }

        if (is_string($value) || is_array($value)) {
            return RobotsMeta::from($value);
        }

        throw new InvalidArgumentException('Robots must be a string, array or RobotsMeta.');
    }

    private function coerceOpenGraph(mixed $value): ?OpenGraph
    {
        if ($value === null || $value instanceof OpenGraph) {
            return $value;
        }

        if (is_array($value)) {
            return OpenGraph::fromArray($value);
        }

        throw new InvalidArgumentException('Open Graph must be an array or OpenGraph.');
    }

    private function coerceTwitter(mixed $value): ?TwitterMeta
    {
        if ($value === null || $value instanceof TwitterMeta) {
            return $value;
        }

        if (is_array($value)) {
            return TwitterMeta::fromArray($value);
        }

        throw new InvalidArgumentException('Twitter must be an array or TwitterMeta.');
    }
}
