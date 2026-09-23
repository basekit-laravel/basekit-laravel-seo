<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Override;

/**
 * JSON-LD WebPage schema.
 */
class WebPageSchema extends Schema
{
    protected string $type = 'WebPage';

    /** @var array<string, mixed> */
    protected array $data = [];

    public function name(?string $name): static
    {
        $this->data['name'] = $name;

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

    public function isPartOf(?string $siteName): static
    {
        $this->data['isPartOf'] = $siteName ? ['@type' => 'WebSite', 'name' => $siteName] : null;

        return $this;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function breadcrumb(array $items): static
    {
        if ($items === []) {
            return $this;
        }

        $this->data['breadcrumb'] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values($items),
        ];

        return $this;
    }

    #[Override]
    protected function attributes(): array
    {
        return $this->data;
    }
}
