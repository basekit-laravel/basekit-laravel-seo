<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Contracts;

use BasekitLaravel\BasekitLaravelSeo\SeoData;

/**
 * Resolves SEO metadata for a supported subject.
 *
 * Implementations are discovered through the container tag
 * {@see SeoManager::RESOLVER_TAG} and registered by binding the class and
 * tagging it in a service provider. The first resolver whose `supports()`
 * returns true wins; later resolvers are not consulted.
 */
interface SeoResolver
{
    /**
     * Whether this resolver can resolve metadata for the given subject.
     */
    public function supports(mixed $subject): bool;

    /**
     * Resolve metadata for the subject, or null when nothing applies.
     *
     * Resolvers must only return safe values: canonical and alternate URLs go
     * through the package validation and must not return unsupported schemes.
     */
    public function resolve(mixed $subject): ?SeoData;
}
