<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

/**
 * JSON-LD Organization schema.
 */
class OrganizationSchema extends Schema
{
    protected string $type = 'Organization';

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

    public function email(?string $email): static
    {
        $this->data['email'] = $email;

        return $this;
    }

    public function logo(?string $logo): static
    {
        $this->data['logo'] = $logo;

        return $this;
    }

    /**
     * @param  array<int, string>  $sameAs
     */
    public function sameAs(array $sameAs): static
    {
        $this->data['sameAs'] = $sameAs ?: null;

        return $this;
    }

    /**
     * @param  array{type?: string, name?: string}  $location
     */
    public function location(array $location): static
    {
        $this->data['location'] = array_filter([
            '@type' => $location['type'] ?? 'Place',
            'name' => $location['name'] ?? null,
        ]);
        $this->data['location'] = $this->data['location'] ?: null;

        return $this;
    }

    #[\Override]
    protected function attributes(): array
    {
        return $this->data;
    }
}
