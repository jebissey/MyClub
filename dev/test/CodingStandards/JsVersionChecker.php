<?php

declare(strict_types=1);

namespace test\CodingStandards;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class JsVersionChecker
{
    private const RULE = 'Les scripts JS doivent utiliser le filtre Latte |version';

    public function __construct(private readonly string $appDir) {}

    /**
     * @return array<string, list<string>>  règle => liste de violations
     */
    public function check(): array
    {
        $violations = [];

        foreach ($this->findLatteFiles() as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            foreach ($this->findScriptTags($content) as [$offset, $src]) {
                if (!$this->isLocalJs($src) || $this->hasVersionFilter($src)) {
                    continue;
                }
                $line = substr_count($content, "\n", 0, $offset) + 1;
                $relative = str_replace($this->appDir . DIRECTORY_SEPARATOR, '', $file);
                $violations[self::RULE][] = "{$relative}:{$line} → {$src}";
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function findLatteFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'latte') {
                $files[] = $fileInfo->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /**
     * @return list<array{int, string}>  [offset de la balise, valeur de src]
     */
    private function findScriptTags(string $content): array
    {
        $pattern = '/<script\b[^>]*?\bsrc\s*=\s*(["\'])(.*?)\1/is';
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $tags = [];
        foreach ($matches[2] as $i => [$src]) {
            $tags[] = [$matches[0][$i][1], $src];
        }

        return $tags;
    }

    private function isLocalJs(string $src): bool
    {
        if (preg_match('#^(https?:)?//#i', trim($src)) === 1) {
            return false; // CDN / ressource externe
        }

        return preg_match('/\.js\b/i', $src) === 1;
    }

    private function hasVersionFilter(string $src): bool
    {
        return preg_match('/\|\s*version\b/', $src) === 1;
    }
}