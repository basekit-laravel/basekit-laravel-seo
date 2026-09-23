<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Services;

use BasekitLaravel\BasekitLaravelSeo\Support\SitemapCatalog;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * Renders aggregated sitemap output as XML strings.
 *
 * It is the package's single sitemap renderer: an entry collection becomes one
 * `<urlset>` document, and a catalog becomes a `<sitemapindex>` when the site
 * had to be split. All values are escaped and control characters are stripped
 * with the same semantics as the published sitemap view, so consumer-provided
 * strings cannot corrupt a document. The byte accounting helpers are used by
 * the chunker so splitting decisions are based on the exact serialized size
 * (UTF-8 byte length, not character count) of each rendered document.
 */
final readonly class SitemapRenderer
{
    public const string XML_DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

    private const string URLSET_PREFIX = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    private const string URLSET_SUFFIX = '</urlset>';

    private const string INDEX_PREFIX = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    private const string INDEX_SUFFIX = '</sitemapindex>';

    public function __construct(private SitemapPaths $paths) {}

    /**
     * The byte length of an empty urlset document (XML declaration, open/close
     * tags and the joined line breaks). This is the fixed scaffolding every
     * document shares, so `scaffoldBytes + sum(entryBytes)` equals the exact
     * serialized size of a rendered urlset.
     */
    public function scaffoldBytes(): int
    {
        return strlen(self::XML_DECLARATION)
            + strlen(self::URLSET_PREFIX)
            + strlen(self::URLSET_SUFFIX)
            + 2;
    }

    /**
     * The byte length a single entry contributes to a rendered urlset,
     * including the line break separating it from the surrounding markup.
     */
    public function entryBytes(SitemapEntry $entry): int
    {
        return strlen($this->entryFragment($entry)) + 1;
    }

    /**
     * Render a complete `<urlset>` document from entries.
     *
     * @param  iterable<SitemapEntry>  $entries
     */
    public function urlset(iterable $entries): string
    {
        $parts = [self::XML_DECLARATION, self::URLSET_PREFIX];

        foreach ($entries as $entry) {
            $parts[] = $this->entryFragment($entry);
        }

        $parts[] = self::URLSET_SUFFIX;

        return implode("\n", $parts);
    }

    /**
     * The serialized `<url>` fragment for one entry, shared by `urlset()` and
     * the byte accounting. Optional fields are omitted when absent; priority
     * falls back to 0.5 exactly like the published sitemap view.
     */
    public function entryFragment(SitemapEntry $entry): string
    {
        $data = $entry->toArray();

        $lines = [
            '    <url>',
            '        <loc>'.$this->escape((string) $data['loc']).'</loc>',
        ];

        if (isset($data['lastmod'])) {
            $lines[] = '        <lastmod>'.$this->escape((string) $data['lastmod']).'</lastmod>';
        }

        if (isset($data['changefreq'])) {
            $lines[] = '        <changefreq>'.$this->escape((string) $data['changefreq']).'</changefreq>';
        }

        $lines[] = '        <priority>'.$this->escape((string) ($data['priority'] ?? '0.5')).'</priority>';
        $lines[] = '    </url>';

        return implode("\n", $lines);
    }

    /**
     * Render a `<sitemapindex>` referencing every document in the catalog.
     *
     * The locations are built from the given trusted origin (already resolved
     * through the package's canonical infrastructure — never from a raw Host
     * header) plus the deterministic chunk paths. Index entries carry no
     * `lastmod`: the package has no meaningful per-document timestamp, and a
     * fabricated date is worse than none.
     */
    public function index(SitemapCatalog $catalog, string $origin): string
    {
        $root = rtrim($origin, '/');

        $parts = [self::XML_DECLARATION, self::INDEX_PREFIX];

        foreach ($catalog->documents() as $document) {
            $parts[] = '    <sitemap>';
            $parts[] = '        <loc>'.$this->escape($root.$this->paths->chunk($document->index)).'</loc>';
            $parts[] = '    </sitemap>';
        }

        $parts[] = self::INDEX_SUFFIX;

        return implode("\n", $parts);
    }

    /**
     * Escape a value for XML and strip disallowed control characters, mirroring
     * the published sitemap view's PHP output escaping.
     */
    private function escape(string $value): string
    {
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;

        return htmlspecialchars($sanitized, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
