<?php

declare(strict_types=1);

namespace test\Core;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Throwable;

/**
 * Extracts the internal links of an HTML page so they can be re-tested
 * in the same session after a simulation (Step IS NOT NULL).
 */
final class LinkExtractor
{
    /**
     * Path prefixes that must never be crawled:
     *  - /api/         : JSON endpoints, not pages
     *  - /user/sign/out : would destroy the session used for the next links
     */
    private const IGNORED_PATH_PREFIXES = [
        '/api/',
        '/user/sign/out',
    ];

    /** Safety net against runaway pages. */
    private const MAX_LINKS_PER_PAGE = 100;

    /**
     * @return list<string> unique root-relative paths (path + query, no fragment)
     */
    public function extract(string $html, string $baseUrl, string $fromPath): array
    {
        if (trim($html) === '') {
            return [];   // 3xx sans corps, réponses vides, etc.
        }

        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        if (!is_string($baseHost) || $baseHost === '') {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadHTML($html);
        } catch (Throwable) {
            $loaded = false;   // ValueError, HTML malformé extrême, etc.
        }
        libxml_clear_errors();
        if (!$loaded) {
            return [];
        }
                $links = [];
        $anchors = (new DOMXPath($dom))->query('//a[@href]');
        if ($anchors === false) {
            return [];
        }

        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }

            $link = $this->normalize($anchor->getAttribute('href'), $baseHost, $fromPath);
            if ($link === null || $link === $fromPath) {
                continue;
            }

            $links[$link] = $link;

            if (count($links) >= self::MAX_LINKS_PER_PAGE) {
                break;
            }
        }

        return array_values($links);
    }

    /**
     * Keeps only same-site, crawlable GET targets. Returns a root-relative
     * "path?query" string, or null when the href must be skipped.
     */
    private function normalize(string $href, string $baseHost, string $fromPath): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        // Protocol-relative URL (//example.com/path) = external.
        if (str_starts_with($href, '//')) {
            return null;
        }

        $parts = parse_url($href);
        if ($parts === false) {
            return null;
        }

        $scheme = $parts['scheme'] ?? null;
        if ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null; // mailto:, tel:, javascript:, data:, ...
        }

        $host = $parts['host'] ?? null;
        if ($host !== null && strcasecmp($host, $baseHost) !== 0) {
            return null; // link to another site
        }

        $path = $parts['path'] ?? '';
        if ($path === '') {
            return null; // pure "#anchor" or "?query" link to the same page
        }

        if (!str_starts_with($path, '/')) {
            $dir = rtrim(str_replace('\\', '/', dirname($fromPath)), '/');
            $path = ($dir === '' || $dir === '.' ? '' : $dir) . '/' . $path;
        }

        foreach (self::IGNORED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return null;
            }
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}
