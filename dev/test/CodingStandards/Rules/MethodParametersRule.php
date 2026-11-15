<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use PhpToken;
use test\CodingStandards\Analysis\MethodSignatureAnalyzer;
use test\CodingStandards\Analysis\PropertyAnalyzer;
use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class MethodParametersRule implements RuleInterface
{
    /**
     * @param int $maxParameters Above this count, parameters should be grouped into a value object.
     * @param int $maxAccessedProperties At or below this count, an object parameter is considered
     *                                   over-sized for what the method actually reads from it.
     */
    public function __construct(
        private SourcePaths $paths,
        private MethodSignatureAnalyzer $methods,
        private PropertyAnalyzer $properties,
        private int $maxParameters = 5,
        private int $maxAccessedProperties = 2,
        private int $minObjectProperties = 4,
    ) {}

    public function label(): string
    {
        return 'Method parameters (too many, or object used for a few properties only)';
    }

    public function check(array $classes): array
    {
        $issues = [];

        /** @var array<string, ClassInfo> $byName */
        $byName = [];
        foreach ($classes as $candidate) {
            $byName[$this->shortName($candidate->className)] = $candidate;
        }

        /** @var array<string, int> $propertyCounts */
        $propertyCounts = [];

        foreach ($classes as $class) {
            $file = $this->paths->rel($class);

            foreach ($this->methods->analyze($class->sourceCode) as $method) {
                // Constructors are dependency lists or value object definitions:
                // another rule's concern, and the advice below does not apply.
                if ($method->name === '__construct') {
                    continue;
                }

                // The signature is imposed by an interface or a parent class:
                // it cannot be changed locally, so the parameter rules do not apply.
                if ($this->isContractBound($class, $method->name, $byName)) {
                    continue;
                }

                $where = "{$class->className}::{$method->name}() ({$file}:{$method->line})";
                $count = count($method->parameters);

                if ($count > $this->maxParameters) {
                    $issues[] = "{$where} takes {$count} parameters (max {$this->maxParameters}): "
                        . 'group the ones that belong together into a value object';
                }

                // Abstract and interface methods have no body to analyze.
                if (!$method->hasBody) {
                    continue;
                }

                foreach ($method->parameters as $parameter) {
                    $accessed = count($parameter->accessedProperties);

                    if (
                        $parameter->type === null
                        || $parameter->usedAsWhole
                        || $accessed === 0
                        || $accessed > $this->maxAccessedProperties
                    ) {
                        continue;
                    }

                    $typeName = ltrim($parameter->type, '?\\');
                    if (str_contains($typeName, '|')) {
                        continue;
                    }

                    $target = $byName[$this->shortName($typeName)] ?? null;
                    if ($target === null || $target->kind !== 'class') {
                        continue;
                    }

                    $total = $propertyCounts[$target->className] ??= $this->publicPropertyCount($target);

                    // Small objects are cheap to pass around and often carry meaning on their own.
                    if ($total < $this->minObjectProperties || $total <= $accessed) {
                        continue;
                    }

                    $list = implode(', ', array_map(
                        static fn(string $property): string => "\${$parameter->name}->{$property}",
                        $parameter->accessedProperties,
                    ));

                    $issues[] = "{$where} receives {$typeName} \${$parameter->name} ({$total} public properties) "
                        . "but only reads {$list}: pass these values directly";
                }
            }
        }

        return $issues;
    }

    private function publicPropertyCount(ClassInfo $class): int
    {
        $count = 0;

        foreach ($this->properties->analyze($class->sourceCode)->properties as $property) {
            if ($property['visibility'] === 'public' && !$property['static']) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Strips any namespace so that "\app\models\Event" and "Event" share the same key.
     */
    private function shortName(string $name): string
    {
        $position = strrpos($name, '\\');

        return $position === false ? $name : substr($name, $position + 1);
    }

    /**
     * True when a parent class or an interface (direct or inherited) declares
     * a method with this name.
     *
     * @param array<string, ClassInfo> $byName
     * @param array<string, true> $visited
     */
    private function isContractBound(ClassInfo $class, string $method, array $byName, array $visited = []): bool
    {
        $visited[$class->className] = true;

        foreach ($this->parentNames($class->sourceCode) as $name) {
            $parent = $byName[$name] ?? null;
            if ($parent === null || isset($visited[$parent->className])) {
                continue;
            }

            foreach ($this->methods->analyze($parent->sourceCode) as $candidate) {
                if (strcasecmp($candidate->name, $method) === 0) {
                    return true;
                }
            }

            if ($this->isContractBound($parent, $method, $byName, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Short names listed after "extends" and "implements" in the first
     * class, interface or trait declaration of the file.
     *
     * @return list<string>
     */
    private function parentNames(string $sourceCode): array
    {
        $tokens = array_values(array_filter(
            PhpToken::tokenize($sourceCode),
            static fn(PhpToken $token): bool => !$token->isIgnorable(),
        ));

        foreach ($tokens as $i => $token) {
            if (!$token->is([T_CLASS, T_INTERFACE])) {
                continue;
            }

            // Skip "Foo::class" and anonymous classes.
            $previous = $tokens[$i - 1] ?? null;
            if ($previous !== null && $previous->is([T_DOUBLE_COLON, T_NEW])) {
                continue;
            }

            $names = [];
            for ($j = $i + 2, $count = count($tokens); $j < $count && !$tokens[$j]->is('{'); $j++) {
                if ($tokens[$j]->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                    $names[] = $this->shortName($tokens[$j]->text);
                }
            }

            return $names;
        }

        return [];
    }
}
