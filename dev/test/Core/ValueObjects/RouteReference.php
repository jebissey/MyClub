<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class RouteReference
{
    public function __construct(
        public string $route,
        public string $filePath,
        public int $lineNumber,
        public string $fileType,
        public string $patternType,
        public string $context
    ) {}
    
    public function getRelativePath(string $basePath): string
    {
        return str_replace($basePath . DIRECTORY_SEPARATOR, '', $this->filePath);
    }
}