<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Components;

use BasekitLaravel\BasekitLaravelSeo\SeoData;
use BasekitLaravel\BasekitLaravelSeo\SeoManager;
use BasekitLaravel\BasekitLaravelSeo\Support\Schema;
use BasekitLaravel\BasekitLaravelSeo\Support\Title;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Blade component rendering the finalized SEO head tags.
 *
 * The component is a pure renderer: it resolves the SeoData (from the explicit
 * `data` attribute or the container SeoManager), formats the title suffix, and
 * pre-renders the JSON-LD script blocks in PHP. The Blade view and partials
 * only emit already-safe markup and never run their own resolution logic.
 *
 * The `data` constructor argument is deliberately not a public promoted
 * property: Laravel reserves the name `data` (the Component::data() method),
 * so a public `$data` property would be silently excluded from the view data.
 * The resolved SeoData is instead passed to the view as `$seo`.
 *
 * `data` is also declared after `manager` on purpose: the container resolves a
 * nullable class-typed parameter with a default value as its default only when
 * it is not followed by another class-typed parameter without a default —
 * otherwise SeoData would be auto-instantiated as an empty object instead of
 * falling back to `null` (meaning "not provided").
 *
 * Rendering order is title, description, robots, canonical, alternates, Open
 * Graph, Twitter, JSON-LD. When the package is disabled via the `enabled`
 * config, nothing is emitted.
 */
final class Head extends Component
{
    private const TITLE_SEPARATOR = ' | ';

    private const DEFAULT_VIEW = 'basekit-laravel-seo::components.head';

    public function __construct(
        private readonly SeoManager $manager,
        private ?SeoData $data = null,
    ) {}

    public function render(): View
    {
        $seo = $this->resolvedData();

        return view(
            (string) config('basekit-laravel-seo.views.head', self::DEFAULT_VIEW),
            [
                'seo' => $seo,
                'title' => Title::withSuffix(
                    $seo->title,
                    (string) config('basekit-laravel-seo.defaults.title_suffix', ''),
                    self::TITLE_SEPARATOR,
                ),
                // Twitter defaults come from the config; the card is only rendered
                // for pages that already carry explicit Twitter metadata.
                'twitterCard' => $seo->twitter !== null
                    ? (string) ($seo->twitter->card ?? config('basekit-laravel-seo.defaults.twitter_card', 'summary_large_image'))
                    : null,
                'schemas' => array_map(
                    static fn (Schema|array $schema): string => $schema instanceof Schema
                        ? $schema->render()
                        : '<script type="application/ld+json">'.json_encode($schema, Schema::ENCODE_FLAGS).'</script>',
                    $seo->schemas,
                ),
            ],
        );
    }

    public function shouldRender(): bool
    {
        return (bool) config('basekit-laravel-seo.enabled', true);
    }

    /**
     * The SeoData to render, either passed explicitly or freshly resolved.
     */
    private function resolvedData(): SeoData
    {
        return $this->data ?? $this->manager->data();
    }
}
