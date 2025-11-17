<?php

declare(strict_types=1);

namespace test\Infrastructure;

use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use test\Core\ValueObjects\RouteReference;

class RouteReferenceExtractor
{
    private const REGEX_LATTE_LINK = '/\{link\s+[\'"]?([^\s\'"}\|]+)[\'"]?/';
    private const REGEX_LATTE_PLINK = '/\{plink\s+[\'"]?([^\s\'"}\|]+)[\'"]?/';
    private const REGEX_LATTE_N_LINK = '/n:href\s*=\s*[\'"]([^\'"]+)[\'"]/';
    private const REGEX_LATTE_HTML_HREF = '/href\s*=\s*[\'"]([^\'"]+)[\'"]/';
    
    private const REGEX_JS_FETCH = '/fetch\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/';
    private const REGEX_JS_AJAX_URL = '/url\s*:\s*[\'"`]([^\'"`]+)[\'"`]/';
    private const REGEX_JS_WINDOW_LOCATION = '/window\.location(?:\.href)?\s*=\s*[\'"`]([^\'"`]+)[\'"`]/';
    private const REGEX_JS_HREF_ATTR = '/\.href\s*=\s*[\'"`]([^\'"`]+)[\'"`]/';
    private const REGEX_JS_AXIOS = '/axios\.[a-z]+\s*\(\s*[\'"`]([^\'"`]+)[\'"`]/';
    
    private array $references = [];

    public function extractReferences(string $directoryPath, array $extensions = ['latte', 'js']): array
    {
        if (!is_dir($directoryPath)) {
            throw new InvalidArgumentException("Directory $directoryPath doesn't exist");
        }

        $this->references = [];
        $this->scanDirectory($directoryPath, $extensions);
        
        return $this->references;
    }

    private function scanDirectory(string $directory, array $extensions): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (!in_array($extension, $extensions)) continue;

            $this->analyzeFile($file->getPathname(), $extension);
        }
    }

    private function analyzeFile(string $filePath, string $extension): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) return;

        $patterns = match($extension) {
            'latte' => $this->getLattePatterns(),
            'js' => $this->getJsPatterns(),
            default => []
        };

        foreach ($patterns as $patternName => $pattern) {
            $this->extractMatches($content, $pattern, $filePath, $extension, $patternName);
        }
    }

    private function getLattePatterns(): array
    {
        return [
            'link' => self::REGEX_LATTE_LINK,
            'plink' => self::REGEX_LATTE_PLINK,
            'n:href' => self::REGEX_LATTE_N_LINK,
            'html-href' => self::REGEX_LATTE_HTML_HREF,
        ];
    }

    private function getJsPatterns(): array
    {
        return [
            'fetch' => self::REGEX_JS_FETCH,
            'ajax' => self::REGEX_JS_AJAX_URL,
            'location' => self::REGEX_JS_WINDOW_LOCATION,
            'href' => self::REGEX_JS_HREF_ATTR,
            'axios' => self::REGEX_JS_AXIOS,
        ];
    }

    private function extractMatches(
        string $content, 
        string $pattern, 
        string $filePath, 
        string $fileType, 
        string $patternType
    ): void {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $route = trim($match[1][0]);
                
                // Filtrer les variables et expressions dynamiques
                //if ($this->isDynamicReference($route)) continue;
                
                $lineNumber = $this->getLineNumber($content, $match[0][1]);
                
                $this->references[] = new RouteReference(
                    route: $route,
                    filePath: $filePath,
                    lineNumber: $lineNumber,
                    fileType: $fileType,
                    patternType: $patternType,
                    context: $this->getContext($content, $match[0][1])
                );
            }
        }
    }

    private function isDynamicReference(string $route): bool
    {
        // Filtrer les variables PHP, JS et expressions
        return preg_match('/[\$\{\}]|^[a-z]+$|^\s*$/', $route) === 1;
    }

    private function getLineNumber(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function getContext(string $content, int $offset, int $contextLength = 100): string
    {
        $start = max(0, $offset - $contextLength);
        $length = min(strlen($content) - $start, $contextLength * 2);
        
        return trim(substr($content, $start, $length));
    }

    /**
     * Groupe les références par route
     */
    public function groupByRoute(array $references): array
    {
        $grouped = [];
        foreach ($references as $ref) {
            $grouped[$ref->route][] = $ref;
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Filtre les références par type de fichier
     */
    public function filterByFileType(array $references, string $fileType): array
    {
        return array_filter($references, fn($ref) => $ref->fileType === $fileType);
    }
}
