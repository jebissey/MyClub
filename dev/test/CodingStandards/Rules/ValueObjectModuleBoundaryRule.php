<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\Analysis\ValueObjectReferenceAnalyzer;
use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class ValueObjectModuleBoundaryRule implements RuleInterface
{
    private const SHARED_MODULE = 'Common';

    public function __construct(
        private SourcePaths $paths,
        private ValueObjectReferenceAnalyzer $analyzer,
    ) {
    }

    public function label(): string
    {
        return 'ValueObjects of another module used (only Common ValueObjects can be shared)';
    }

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            $relative = $this->paths->rel($class);
            $module = $this->analyzer->moduleOf($relative);
            if ($module === null) {
                continue;
            }

            foreach ($this->analyzer->references($class->sourceCode) as $reference) {
                if ($reference['module'] === $module || $reference['module'] === self::SHARED_MODULE) {
                    continue;
                }

                $issues[] = sprintf(
                    '%s uses %s\\%s from module %s, move it to Common/valueObjects or stop sharing it',
                    $relative,
                    $reference['module'],
                    $reference['name'],
                    $reference['module'],
                );
            }
        }

        return $issues;
    }
}