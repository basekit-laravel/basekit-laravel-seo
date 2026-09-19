<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

/**
 * JSON-LD Article schema (also covers BlogPosting / CreativeWork via type()).
 */
class ArticleSchema extends Schema
{
    protected string $type = 'Article';

    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * Override the schema @type (e.g. 'BlogPosting', 'CreativeWork').
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function headline(?string $headline): static
    {
        $this->data['headline'] = $headline;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->data['description'] = $description;

        return $this;
    }

    public function url(?string $url): static
    {
        $this->data['url'] = $url;

        return $this;
    }

    public function image(?string $image): static
    {
        $this->data['image'] = $image;

        return $this;
    }

    public function datePublished(?string $datePublished): static
    {
        $this->data['datePublished'] = $datePublished;

        return $this;
    }

    public function dateModified(?string $dateModified): static
    {
        $this->data['dateModified'] = $dateModified;

        return $this;
    }

    public function author(?string $authorName, string $authorType = 'Person'): static
    {
        $this->data['author'] = array_filter([
            '@type' => $authorType,
            'name' => $authorName,
        ]);

        return $this;
    }

    public function publisher(OrganizationSchema $organization): static
    {
        $this->data['publisher'] = $organization->toArray();

        return $this;
    }

    public function mainEntityOfPage(?string $url): static
    {
        $this->data['mainEntityOfPage'] = $url ? ['@type' => 'WebPage', '@id' => $url] : null;

        return $this;
    }

    /**
     * Set an ISO-8601 date from anything Carbon can parse, if provided.
     */
    public function datePublishedFrom(mixed $date): static
    {
        return $this->datePublished($date === null ? null : (string) $date);
    }

    #[\Override]
    protected function attributes(): array
    {
        return $this->data;
    }
}
