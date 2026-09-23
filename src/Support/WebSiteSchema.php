<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Override;

/**
 * JSON-LD WebSite schema (often wrapped with a SearchAction).
 */
class WebSiteSchema extends Schema
{
    protected string $type = 'WebSite';

    /** @var array<string, mixed> */
    protected array $data = [];

    public function name(?string $name): static
    {
        $this->data['name'] = $name;

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

    public function publisher(OrganizationSchema $organization): static
    {
        $this->data['publisher'] = $organization->toArray();

        return $this;
    }

    /**
     * Attach a potential SearchAction (e.g. for a site search endpoint).
     *
     * @param  array<string, string>  $action
     */
    public function potentialAction(array $action): static
    {
        $this->data['potentialAction'] = array_filter([
            '@type' => $action['type'] ?? 'SearchAction',
            'target' => $action['target'] ?? null,
            'query-input' => $action['query_input'] ?? null,
        ]);

        return $this;
    }

    #[Override]
    protected function attributes(): array
    {
        return $this->data;
    }
}
