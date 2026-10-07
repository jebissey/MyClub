<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;

final readonly class FinalReadonlyRule implements RuleInterface
{
    public function __construct(
        private SourcePaths $paths,
        private string $subpath,
        private string $label,
        private ?string $requiredSuffix = null,
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class' || !$this->paths->inSubpath($class, $this->subpath)) {
                continue;
            }

            $where = "{$class->className} ({$this->paths->rel($class)})";

            // final + abstract is contradictory in PHP: a base class can only be readonly.
            if ($class->isAbstract) {
                if (!$class->isReadonly) {
                    $issues[] = "{$where} should be 'readonly' (base class)";
                }
            } elseif (!$class->isFinal || !$class->isReadonly) {
                $issues[] = "{$where} must be 'final readonly'";
            }

            if ($this->requiredSuffix !== null && !str_ends_with($class->className, $this->requiredSuffix)) {
                $issues[] = "{$where} must be suffixed '{$this->requiredSuffix}'";
            }
        }

        return $issues;
    }
}