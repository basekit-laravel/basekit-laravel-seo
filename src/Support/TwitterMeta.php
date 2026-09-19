<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Stringable;

/**
 * Twitter Card metadata for a page.
 *
 * The card type defaults to summary_large_image. The image URL is validated
 * against the same safety rules as canonical URLs.
 */
final readonly class TwitterMeta implements Stringable
{
    public const CARD_SUMMARY = 'summary';

    public const CARD_SUMMARY_LARGE_IMAGE = 'summary_large_image';

    public const CARD_APP = 'app';

    public const CARD_PLAYER = 'player';

    public function __construct(
        public ?string $card = self::CARD_SUMMARY_LARGE_IMAGE,
        public ?string $site = null,
        public ?string $creator = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
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
            card: isset($data['card']) ? (string) $data['card'] : null,
            site: isset($data['site']) ? (string) $data['site'] : null,
            creator: isset($data['creator']) ? (string) $data['creator'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
        );

        if (isset($data['image'])) {
            $object = $object->withImage((string) $data['image']);
        }

        return $object;
    }

    public function withCard(?string $card): self
    {
        return $this->with(['card' => $card === null ? null : strtolower(trim($card))]);
    }

    public function withSite(?string $site): self
    {
        return $this->with(['site' => $site]);
    }

    public function withCreator(?string $creator): self
    {
        return $this->with(['creator' => $creator]);
    }

    public function withTitle(?string $title): self
    {
        return $this->with(['title' => $title]);
    }

    public function withDescription(?string $description): self
    {
        return $this->with(['description' => $description]);
    }

    public function withImage(?string $image): self
    {
        return $this->with(['image' => $image === null ? null : CanonicalUrl::from($image)->toString()]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'card' => $this->card,
            'site' => $this->site,
            'creator' => $this->creator,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function toString(): string
    {
        $parts = [];

        foreach ($this->toArray() as $key => $value) {
            $parts[] = 'twitter:'.$key.'="'.$value.'"';
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
            card: array_key_exists('card', $changes) ? $changes['card'] : $this->card,
            site: array_key_exists('site', $changes) ? $changes['site'] : $this->site,
            creator: array_key_exists('creator', $changes) ? $changes['creator'] : $this->creator,
            title: array_key_exists('title', $changes) ? $changes['title'] : $this->title,
            description: array_key_exists('description', $changes) ? $changes['description'] : $this->description,
            image: array_key_exists('image', $changes) ? $changes['image'] : $this->image,
        );
    }
}
