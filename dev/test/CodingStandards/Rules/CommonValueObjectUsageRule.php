<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\Analysis\ValueObjectReferenceAnalyzer;
use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class CommonValueObjectUsageRule implements RuleInterface
{
    private const SHARED_MODULE = 'Common';
    private const OUTSIDE_MODULES = '(outside modules)';
    private const COMMON_VO_PATTERN = '#^modules/Common/valueObjects/([^/]+)\.php$#';

    public function __construct(
        private SourcePaths $paths,
        private ValueObjectReferenceAnalyzer $analyzer,
    ) {
    }

    public function label(): string
    {
        return 'Common ValueObjects used by a single module (move them into that module)';
    }

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array
    {
        $commonValueObjects = $this->commonValueObjects($classes);
        if ($commonValueObjects === []) {
            return [];
        }

        /** @var array<string, array<string, true>> $users ValueObject name => set of user modules */
        $users = [];

        foreach ($classes as $class) {
            $relative = $this->paths->rel($class);
            $bucket = $this->analyzer->moduleOf($relative) ?? self::OUTSIDE_MODULES;
            $selfName = pathinfo($relative, PATHINFO_FILENAME);

            // Explicit references (use statements, inline FQCN)
            foreach ($this->analyzer->references($class->sourceCode) as $reference) {
                if ($reference['module'] === self::SHARED_MODULE
                    && isset($commonValueObjects[$reference['name']])
                    && $reference['name'] !== $selfName
                ) {
                    $users[$reference['name']][$bucket] = true;
                }
            }

            // ValueObjects of the same namespace are referenced by short name, without "use"
            if (preg_match(self::COMMON_VO_PATTERN, $relative) === 1) {
                foreach (array_keys($commonValueObjects) as $name) {
                    if ($name !== $selfName
                        && preg_match('~\b' . preg_quote($name, '~') . '\b~', $class->sourceCode) === 1
                    ) {
                        $users[$name][self::SHARED_MODULE] = true;
                    }
                }
            }
        }

        $issues = [];
        foreach ($commonValueObjects as $name => $relative) {
            $buckets = array_keys($users[$name] ?? []);

            if (count($buckets) === 1
                && $buckets[0] !== self::SHARED_MODULE
                && $buckets[0] !== self::OUTSIDE_MODULES
            ) {
                $issues[] = sprintf(
                    '%s is only used by module %s, move it to modules/%s/valueObjects',
                    $relative,
                    $buckets[0],
                    $buckets[0],
                );
            }
        }

        return $issues;
    }

    /**
     * @param list<ClassInfo> $classes
     * @return array<string, string> ValueObject name => relative path
     */
    private function commonValueObjects(array $classes): array
    {
        $result = [];
        foreach ($classes as $class) {
            $relative = $this->paths->rel($class);
            if (preg_match(self::COMMON_VO_PATTERN, $relative, $m) === 1) {
                $result[$m[1]] = $relative;
            }
        }

        return $result;
    }
}