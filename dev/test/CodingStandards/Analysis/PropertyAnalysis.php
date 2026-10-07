<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

/**
 * @phpstan-type PropertyData array{name: string, line: int, visibility: string, readonly: bool, static: bool, typed: bool, promoted: bool, hasDefault: bool}
 */
final readonly class PropertyAnalysis
{
    /**
     * @param list<PropertyData> $properties
     */
    public function __construct(
        public array $properties,
        public string $fullCode,
        public string $codeOutsideConstructor,
        public bool $usesClone,
    ) {
    }
}