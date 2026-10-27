<?php

declare(strict_types=1);

namespace test\CodingStandards\ValueObjects;

final readonly class MethodSignature
{
    /**
     * @param list<ParameterInfo> $parameters
     */
    public function __construct(
        public string $name,
        public int $line,
        public bool $hasBody,
        public array $parameters,
    ) {
    }
}
