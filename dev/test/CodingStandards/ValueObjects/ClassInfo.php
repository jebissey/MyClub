<?php

declare(strict_types=1);

namespace test\CodingStandards\ValueObjects;

final readonly class ClassInfo
{
    public function __construct(
        public string $filePath,
        public string $className,
        public string $kind, // 'class' | 'interface' | 'trait' | 'enum'
        public bool $isFinal,
        public bool $isAbstract,
        public bool $isReadonly,
        public ?string $extends,
        public string $sourceCode,
    ) {
    }

    public function relativePath(string $appDir): string
    {
        return ltrim(substr($this->filePath, strlen($appDir)), '/');
    }
}