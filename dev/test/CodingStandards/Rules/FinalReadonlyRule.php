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
        // Collect every class that is extended by another one: it cannot be final.
        $extended = [];
        foreach ($classes as $class) {
            if ($class->extends !== null) {
                $extended[$class->extends] = true;
            }
        }

        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class' || !$this->paths->inSubpath($class, $this->subpath)) {
                continue;
            }

            $where = "{$class->className} ({$this->paths->rel($class)})";

            // A base class (abstract, or concrete but extended) cannot be final:
            // it can only be required to be readonly.
            if ($class->isAbstract || isset($extended[$class->className])) {
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