<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;

final readonly class FinalClassesRule implements RuleInterface
{
    /** @param list<string> $coveredSubpaths Subpaths already enforced by a dedicated rule. */
    public function __construct(private SourcePaths $paths, private array $coveredSubpaths)
    {
    }

    public function label(): string
    {
        return 'Classes not final (and not extended)';
    }

    public function check(array $classes): array
    {
        $extended = [];
        foreach ($classes as $class) {
            if ($class->extends !== null) {
                $extended[$class->extends] = true;
            }
        }

        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class' || $class->isAbstract || $class->isFinal) {
                continue;
            }

            if ($this->isCovered($class) || isset($extended[$class->className])) {
                continue;
            }

            $issues[] = "{$class->className} ({$this->paths->rel($class)}) should be 'final' (no class seems to extend it)";
        }

        return $issues;
    }

    private function isCovered(\test\CodingStandards\ValueObjects\ClassInfo $class): bool
    {
        foreach ($this->coveredSubpaths as $subpath) {
            if ($this->paths->inSubpath($class, $subpath)) {
                return true;
            }
        }

        return false;
    }
}