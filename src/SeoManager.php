<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo;

use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\Services\CanonicalUrlResolver;
use BasekitLaravel\BasekitLaravelSeo\Support\CanonicalUrl;
use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;
use BasekitLaravel\BasekitLaravelSeo\Support\RobotsMeta;
use BasekitLaravel\BasekitLaravelSeo\Support\Schema;
use BasekitLaravel\BasekitLaravelSeo\Support\Title;
use BasekitLaravel\BasekitLaravelSeo\Support\TwitterMeta;
use Illuminate\Contracts\Container\Container;

/**
 * Central entry point for building SEO metadata.
 *
 * Resolves the final SeoData for a request by merging three layers, in
 * priority order:
 *
 *   1. the package/application defaults (config),
 *   2. the data produced by the first supporting SeoResolver for the subject,
 *   3. explicit overrides registered through the fluent setters.
 *
 * Lists (alternates, schemas) are concatenated across layers; scalar non-null
 * values from higher layers win. The class is a container scoped binding so
 * it is fresh per request.
 */
final class SeoManager
{
    public const RESOLVER_TAG = 'basekit-laravel-seo.resolvers';

    /**
     * Explicit overrides accumulated through the fluent API.
     */
    private SeoData $explicit;

    private mixed $subject = null;

    private bool $hasSubject = false;

    public function __construct(private readonly Container $container)
    {
        $this->explicit = SeoData::make();
    }

    public function title(?string $title): static
    {
        $this->explicit = $this->explicit->withTitle($title);

        return $this;
    }

    public function description(?string $description): static
    {
        $this->explicit = $this->explicit->withDescription($description);

        return $this;
    }

    public function canonicalUrl(string|CanonicalUrl|null $url): static
    {
        $this->explicit = $this->explicit->withCanonicalUrl($url);

        return $this;
    }

    /**
     * @param  array<int, string>  $directives
     */
    public function robots(RobotsMeta|string|array|null $directives): static
    {
        $this->explicit = $this->explicit->withRobots($directives);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $openGraph
     */
    public function openGraph(OpenGraph|array|null $openGraph): static
    {
        $this->explicit = $this->explicit->withOpenGraph($openGraph);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $twitter
     */
    public function twitter(TwitterMeta|array|null $twitter): static
    {
        $this->explicit = $this->explicit->withTwitter($twitter);

        return $this;
    }

    public function locale(?string $locale): static
    {
        $this->explicit = $this->explicit->withLocale($locale);

        return $this;
    }

    public function alternate(string $hreflang, string|CanonicalUrl $url): static
    {
        $this->explicit = $this->explicit->withAlternate($hreflang, $url);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function schema(Schema|array $schema): static
    {
        $this->explicit = $this->explicit->withSchema($schema);

        return $this;
    }

    /**
     * Point the manager at a subject (model, page class, request, array, ...).
     *
     * Resolvers decide on their own whether they support the subject.
     */
    public function for(mixed $subject): static
    {
        $this->subject = $subject;
        $this->hasSubject = true;

        return $this;
    }

    /**
     * Resolve the final metadata for the current state.
     */
    public function data(): SeoData
    {
        $defaults = $this->defaults();
        $resolved = $this->hasSubject ? $this->resolve($this->subject) : null;

        return $this->merge($this->merge($defaults, $resolved), $this->explicit);
    }

    /**
     * The final resolved title with the configured suffix applied.
     *
     * The suffix is a rendering concern, so it is applied here on demand and
     * never stored in SeoData. The suffix is based on the resolved title (after
     * the defaults → resolver → explicit merge), not just the explicit layer.
     */
    public function titleWithSuffix(string $separator = ' | '): ?string
    {
        $suffix = (string) config('basekit-laravel-seo.defaults.title_suffix', '');

        return Title::withSuffix($this->data()->title, $suffix, $separator);
    }

    /**
     * Forget accumulated state so the instance can be reused (e.g. in tests or
     * long-running workers).
     */
    public function reset(): static
    {
        $this->explicit = SeoData::make();
        $this->subject = null;
        $this->hasSubject = false;

        return $this;
    }

    /**
     * The first non-null value wins for scalars; Open Graph and Twitter merge
     * field by field; robots replaces the whole set; lists concatenate.
     */
    private function merge(SeoData $lower, ?SeoData $higher): SeoData
    {
        if ($higher === null) {
            return $lower;
        }

        return SeoData::make()
            ->withTitle($higher->title ?? $lower->title)
            ->withDescription($higher->description ?? $lower->description)
            ->withCanonicalUrl($higher->canonicalUrl ?? $lower->canonicalUrl)
            ->withRobots($higher->robots ?? $lower->robots)
            ->withLocale($higher->locale ?? $lower->locale)
            ->withOpenGraph($this->mergeOpenGraph($lower->openGraph, $higher->openGraph))
            ->withTwitter($this->mergeTwitter($lower->twitter, $higher->twitter))
            ->withAlternates([...$lower->alternates, ...$higher->alternates])
            ->withSchemas([...$lower->schemas, ...$higher->schemas]);
    }

    private function mergeOpenGraph(?OpenGraph $lower, ?OpenGraph $higher): ?OpenGraph
    {
        if ($higher === null) {
            return $lower;
        }

        return OpenGraph::make()
            ->withTitle($higher->title ?? $lower?->title)
            ->withDescription($higher->description ?? $lower?->description)
            ->withType($higher->type ?? $lower?->type)
            ->withImage($higher->image ?? $lower?->image)
            ->withUrl($higher->url ?? $lower?->url)
            ->withSiteName($higher->siteName ?? $lower?->siteName);
    }

    private function mergeTwitter(?TwitterMeta $lower, ?TwitterMeta $higher): ?TwitterMeta
    {
        if ($higher === null) {
            return $lower;
        }

        return TwitterMeta::make()
            ->withCard($higher->card ?? $lower?->card)
            ->withSite($higher->site ?? $lower?->site)
            ->withCreator($higher->creator ?? $lower?->creator)
            ->withTitle($higher->title ?? $lower?->title)
            ->withDescription($higher->description ?? $lower?->description)
            ->withImage($higher->image ?? $lower?->image);
    }

    /**
     * The layer-1 defaults derived from the package configuration.
     */
    private function defaults(): SeoData
    {
        $siteName = (string) config('basekit-laravel-seo.defaults.site_name', 'Basekit');
        $description = (string) config('basekit-laravel-seo.defaults.description', '');
        $locale = (string) config('basekit-laravel-seo.defaults.locale', 'en');
        $ogImage = config('basekit-laravel-seo.defaults.og_image');

        $openGraph = OpenGraph::make();

        if ($siteName !== '') {
            $openGraph = $openGraph->withSiteName($siteName);
        }

        if (is_string($ogImage) && $ogImage !== '') {
            $safeImage = CanonicalUrl::tryFrom($ogImage);

            if ($safeImage !== null) {
                $openGraph = $openGraph->withImage($safeImage->toString());
            }
        }

        return SeoData::make()
            ->withDescription($description !== '' ? $description : null)
            ->withLocale($locale !== '' ? $locale : null)
            ->withOpenGraph($openGraph)
            ->withCanonicalUrl($this->resolveDefaultCanonical()?->toString());
    }

    /**
     * Resolve the default canonical URL through the shared trusted-origin
     * resolver (config base_url, then app.url, then an allow-listed request
     * host). When no safe base can be established, none is invented.
     */
    private function resolveDefaultCanonical(): ?CanonicalUrl
    {
        return $this->container->make(CanonicalUrlResolver::class)->resolve();
    }

    /**
     * Ask tagged resolvers about the subject; the first supporter wins.
     */
    private function resolve(mixed $subject): ?SeoData
    {
        $resolvers = $this->container->tagged(self::RESOLVER_TAG);

        foreach ($resolvers as $resolver) {
            if (! $resolver instanceof SeoResolver) {
                continue;
            }

            if ($resolver->supports($subject)) {
                return $resolver->resolve($subject);
            }
        }

        return null;
    }
}
