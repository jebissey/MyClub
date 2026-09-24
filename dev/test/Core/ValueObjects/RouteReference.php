<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

class RouteReference
{
    public function __construct(
        public readonly string $route,
        public readonly string $filePath,
        public readonly int $lineNumber,
        public readonly string $fileType,
        public readonly string $patternType,
        public readonly string $context
    ) {}
    
    public function getRelativePath(string $basePath): string
    {
        return str_replace($basePath . DIRECTORY_SEPARATOR, '', $this->filePath);
    }
}