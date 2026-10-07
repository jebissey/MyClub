<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\Analysis\PropertyAnalysis;
use test\CodingStandards\Analysis\PropertyAnalyzer;
use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

/**
 * @phpstan-import-type PropertyData from PropertyAnalysis
 */
final readonly class ReadonlyPropertiesRule implements RuleInterface
{
    /** @param list<string> $coveredSubpaths Subpaths already enforced by a dedicated rule. */
    public function __construct(
        private SourcePaths $paths,
        private PropertyAnalyzer $analyzer,
        private array $coveredSubpaths,
    ) {
    }

    public function label(): string
    {
        return 'Properties that should be readonly';
    }

    public function check(array $classes): array
    {
        $issues = [];

        /** @var array<string, ClassInfo> $byName */
        $byName = [];
        foreach ($classes as $candidate) {
            $byName[$this->shortName($candidate->className)] = $candidate;
        }

        foreach ($classes as $class) {
            if ($class->kind !== 'class' || $class->isReadonly || $this->isCovered($class)) {
                continue;
            }

            $analysis = $this->analyzer->analyze($class->sourceCode);
            if ($analysis->properties === []) {
                continue;
            }

            /** @var array<string, int> $eligible */
            $eligible = [];
            $blocked = false;

            // Computed lazily, once per class.
            /** @var array<string, true>|null $inherited */
            $inherited = null;

            foreach ($analysis->properties as $property) {
                if ($property['readonly']) {
                    continue;
                }

                $inherited ??= $this->inheritedPropertyNames($class, $byName);

                // A property redeclared from a parent class cannot become readonly:
                // PHP forbids changing the readonly flag when overriding a property.
                if (isset($inherited[$property['name']])) {
                    $blocked = true;
                    continue;
                }

                if ($this->canBeReadonly($property, $class, $analysis)) {
                    $eligible[$property['name']] = $property['line'];
                } else {
                    $blocked = true;
                }
            }

            if ($eligible === []) {
                continue;
            }

            $file = $this->paths->rel($class);

            // A readonly class cannot extend a non-readonly one, hence the extends check.
            if (!$blocked && $class->isFinal && $class->extends === null) {
                $issues[] = "{$class->className} ({$file}) should be 'final readonly' (no property is ever reassigned)";
                continue;
            }

            foreach ($eligible as $name => $line) {
                $issues[] = "{$class->className}::\${$name} ({$file}:{$line}) should be 'readonly' (never reassigned)";
            }
        }

        return $issues;
    }

    /**
     * @param PropertyData $property
     */
    private function canBeReadonly(array $property, ClassInfo $class, PropertyAnalysis $analysis): bool
    {
        // A readonly property must be typed, non-static and have no default value.
        if (!$property['typed'] || $property['static'] || $property['hasDefault']) {
            return false;
        }

        // Public properties can be mutated from outside, which cannot be
        // detected here. Protected ones are only safe in a final class.
        $visibilityIsSafe = $property['visibility'] === 'private'
            || ($property['visibility'] === 'protected' && $class->isFinal);

        if (!$visibilityIsSafe || $analysis->usesClone) {
            return false;
        }

        // A promoted property is already initialized by the constructor, so any
        // assignment (even in the constructor body) is a reassignment. A declared
        // property may be assigned once in the constructor.
        $code = $property['promoted'] ? $analysis->fullCode : $analysis->codeOutsideConstructor;

        return !$this->analyzer->isPropertyReassigned($code, $property['name']);
    }

    /**
     * Names of the properties declared in the ancestors of $class.
     *
     * @param array<string, ClassInfo> $byName
     * @return array<string, true>
     */
    private function inheritedPropertyNames(ClassInfo $class, array $byName): array
    {
        $names = [];
        $seen = [$this->shortName($class->className) => true];
        $parentName = $class->extends !== null ? $this->shortName($class->extends) : null;

        while ($parentName !== null && isset($byName[$parentName]) && !isset($seen[$parentName])) {
            $seen[$parentName] = true;
            $parent = $byName[$parentName];

            foreach ($this->analyzer->analyze($parent->sourceCode)->properties as $property) {
                $names[$property['name']] = true;
            }

            $parentName = $parent->extends !== null ? $this->shortName($parent->extends) : null;
        }

        return $names;
    }

    /**
     * Strips any namespace and leading backslash so that "\app\apis\AbstractApi"
     * and "AbstractApi" resolve to the same key.
     */
    private function shortName(string $name): string
    {
        $position = strrpos($name, '\\');

        return $position === false ? $name : substr($name, $position + 1);
    }

    private function isCovered(ClassInfo $class): bool
    {
        foreach ($this->coveredSubpaths as $subpath) {
            if ($this->paths->inSubpath($class, $subpath)) {
                return true;
            }
        }

        return false;
    }
}