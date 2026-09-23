<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Stringable;

/**
 * Open Graph metadata for a page.
 *
 * URLs (image, url) are validated against the same safety rules as canonical
 * URLs; unsafe values are rejected with an InvalidArgumentException and must
 * not reach rendered output.
 */
final readonly class OpenGraph implements Stringable
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $image = null,
        public ?string $url = null,
        public ?string $siteName = null,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $object = new self(
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: isset($data['type']) ? (string) $data['type'] : null,
            siteName: isset($data['site_name']) ? (string) $data['site_name'] : (isset($data['siteName']) ? (string) $data['siteName'] : null),
        );

        if (isset($data['image'])) {
            $object = $object->withImage((string) $data['image']);
        }

        if (isset($data['url'])) {
            return $object->withUrl((string) $data['url']);
        }

        return $object;
    }

    public function withTitle(?string $title): self
    {
        return $this->with(['title' => $title]);
    }

    public function withDescription(?string $description): self
    {
        return $this->with(['description' => $description]);
    }

    public function withType(?string $type): self
    {
        return $this->with(['type' => $type]);
    }

    public function withImage(?string $image): self
    {
        return $this->with(['image' => $image === null ? null : CanonicalUrl::from($image)->toString()]);
    }

    public function withUrl(?string $url): self
    {
        return $this->with(['url' => $url === null ? null : CanonicalUrl::from($url)->toString()]);
    }

    public function withSiteName(?string $siteName): self
    {
        return $this->with(['siteName' => $siteName]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'image' => $this->image,
            'url' => $this->url,
            'site_name' => $this->siteName,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function toString(): string
    {
        $parts = [];

        foreach ($this->toArray() as $key => $value) {
            $parts[] = 'og:'.$key.'="'.$value.'"';
        }

        return implode(' ', $parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Build a new instance applying only the given changes.
     *
     * @param  array<string, string|null>  $changes
     */
    private function with(array $changes): self
    {
        return new self(
            title: array_key_exists('title', $changes) ? $changes['title'] : $this->title,
            description: array_key_exists('description', $changes) ? $changes['description'] : $this->description,
            type: array_key_exists('type', $changes) ? $changes['type'] : $this->type,
            image: array_key_exists('image', $changes) ? $changes['image'] : $this->image,
            url: array_key_exists('url', $changes) ? $changes['url'] : $this->url,
            siteName: array_key_exists('siteName', $changes) ? $changes['siteName'] : $this->siteName,
        );
    }
}
