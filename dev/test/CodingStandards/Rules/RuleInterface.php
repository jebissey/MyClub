<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\ValueObjects\ClassInfo;

interface RuleInterface
{
    public function label(): string;

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array;
}