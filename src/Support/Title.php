<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Support;

/**
 * Title formatting helpers.
 *
 * The title suffix is a rendering concern, so the suffix is applied on demand
 * here rather than being stored in SeoData.
 */
final class Title
{
    /**
     * Append a suffix to a title unless it is already present.
     *
     * A null or blank title is returned unchanged and a blank suffix is ignored
     * so the raw title never gets trailing separators.
     */
    public static function withSuffix(?string $title, ?string $suffix, string $separator = ' | '): ?string
    {
        if ($title === null || trim($title) === '') {
            return $title;
        }

        $suffix = trim((string) $suffix);
        $title = trim($title);

        if ($suffix === '') {
            return $title;
        }

        if (str_ends_with($title, $suffix)) {
            return $title;
        }

        return $title.$separator.$suffix;
    }
}
