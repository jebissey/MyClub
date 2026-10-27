<?php

declare(strict_types=1);

namespace test\CodingStandards\ValueObjects;

final readonly class ParameterInfo
{
    /**
     * @param list<string> $accessedProperties Distinct members read as "$param->member" (not method calls).
     */
    public function __construct(
        public string $name,
        public ?string $type,
        public array $accessedProperties,
        public bool $usedAsWhole,
    ) {
    }
}
