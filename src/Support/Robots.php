<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

use Illuminate\Http\Response;

/**
 * Builds robots.txt content from a set of directives.
 *
 * Renderable as a plain-text response body; trailing lines are trimmed so the
 * output has no stray blank lines.
 *
 * @phpstan-consistent-constructor
 */
class Robots
{
    /** @var array<int, string> */
    protected array $userAgents;

    /** @var array<int, string> */
    protected array $allow;

    /** @var array<int, string> */
    protected array $disallow;

    protected ?string $sitemap;

    /**
     * @param  array<int, string>  $userAgents
     * @param  array<int, string>  $allow
     * @param  array<int, string>  $disallow
     */
    public function __construct(
        array $userAgents = ['*'],
        array $allow = ['/'],
        array $disallow = [],
        ?string $sitemap = null,
    ) {
        $this->userAgents = array_map(self::sanitizeLine(...), $userAgents) ?: ['*'];
        $this->allow = array_map(self::sanitizeLine(...), $allow);
        $this->disallow = array_map(self::sanitizeLine(...), $disallow);
        $this->sitemap = $sitemap === null ? null : self::sanitizeLine($sitemap);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function make(array $config = []): static
    {
        return new static(
            userAgents: $config['user_agents'] ?? ['*'],
            allow: $config['allow'] ?? ['/'],
            disallow: $config['disallow'] ?? [],
            sitemap: $config['sitemap'] ?? null,
        );
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function disallow(array $paths): static
    {
        $this->disallow = array_values(array_filter(array_map(self::sanitizeLine(...), $paths)));

        return $this;
    }

    public function sitemap(?string $url): static
    {
        $this->sitemap = $url === null ? null : self::sanitizeLine($url);

        return $this;
    }

    public function toString(): string
    {
        $lines = [];

        foreach ($this->userAgents as $agent) {
            $lines[] = 'User-agent: '.$agent;
        }

        foreach ($this->allow as $path) {
            $lines[] = 'Allow: '.$path;
        }

        foreach ($this->disallow as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        if ($this->sitemap !== null) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.$this->sitemap;
        }

        return implode("\n", $lines)."\n";
    }

    public function response(): Response
    {
        return response($this->toString(), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Cut a directive value at the first line break or control character so
     * embedded payloads cannot inject additional directives. Everything after
     * the first control character is discarded.
     */
    private static function sanitizeLine(string $value): string
    {
        $sanitized = preg_replace('/[\x00-\x1F\x7F].*$/s', '', trim($value));

        return (string) $sanitized;
    }
}
